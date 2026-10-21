/*
 * Web push client helpers.
 *
 * Registers the service worker, requests permission at a deliberate moment
 * (the user clicking "enable"), subscribes through PushManager and reports the
 * subscription to Laravel. Also supports unsubscribing.
 */

function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);

    for (let i = 0; i < rawData.length; i += 1) {
        outputArray[i] = rawData.charCodeAt(i);
    }

    return outputArray;
}

async function serviceWorkerRegistration() {
    const registration = await navigator.serviceWorker.register('/sw.js', { scope: '/' });

    return registration;
}

async function currentSubscription() {
    if (!('serviceWorker' in navigator)) {
        return null;
    }

    const registration = await navigator.serviceWorker.ready;

    return registration.pushManager.getSubscription();
}

const NewsPush = {
    supported() {
        return 'serviceWorker' in navigator
            && 'PushManager' in window
            && 'Notification' in window;
    },

    permission() {
        return this.supported() ? Notification.permission : 'unsupported';
    },

    async isSubscribed() {
        return (await currentSubscription()) !== null;
    },

    async registerServiceWorker() {
        if (!this.supported()) {
            return null;
        }

        return serviceWorkerRegistration();
    },

    async subscribe(vapidPublicKey, meta = {}) {
        if (!this.supported()) {
            return { ok: false, reason: 'unsupported' };
        }

        if (!vapidPublicKey) {
            return { ok: false, reason: 'missing-key' };
        }

        const permission = await Notification.requestPermission();

        if (permission !== 'granted') {
            return { ok: false, reason: permission };
        }

        const registration = await serviceWorkerRegistration();
        let subscription = await registration.pushManager.getSubscription();

        if (!subscription) {
            subscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(vapidPublicKey),
            });
        }

        const payload = {
            ...subscription.toJSON(),
            device_label: meta.deviceLabel || null,
        };

        const response = await fetch('/push/subscriptions', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': meta.csrfToken,
            },
            body: JSON.stringify(payload),
        });

        if (!response.ok) {
            return { ok: false, reason: 'server', status: response.status };
        }

        return { ok: true };
    },

    async unsubscribe(meta = {}) {
        const subscription = await currentSubscription();

        if (subscription) {
            await subscription.unsubscribe();
        }

        if (meta.subscriptionId) {
            await fetch('/push/subscriptions/' + meta.subscriptionId, {
                method: 'DELETE',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': meta.csrfToken,
                },
            });
        }

        return { ok: true };
    },
};

window.NewsPush = NewsPush;

export default NewsPush;
