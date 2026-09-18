<x-filament-widgets::widget>
    <x-filament::section>
        <div
            x-data="pushNotificationsWidget({
                vapidPublicKey: @js($this->vapidPublicKey),
                token: @js($this->pushToken),
            })"
            x-init="init()"
            class="flex items-center justify-between gap-4"
        >
            <div>
                <p class="text-sm font-medium text-gray-950 dark:text-white">Notificaciones push</p>
                <p class="text-xs text-gray-500 dark:text-gray-400" x-show="!supported">
                    Tu navegador no soporta notificaciones push.
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400" x-show="supported && permission === 'denied'">
                    Bloqueadas en el navegador — habilitalas desde la configuración del sitio.
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400" x-show="supported && permission !== 'denied' && error" x-text="error"></p>
            </div>

            <template x-if="supported && permission !== 'denied'">
                <x-filament::button
                    x-on:click="subscribed ? unsubscribe() : subscribe()"
                    x-bind:disabled="loading"
                    x-bind:color="subscribed ? 'success' : 'gray'"
                    size="sm"
                >
                    <span x-show="!loading" x-text="subscribed ? '🔔 Activadas' : '🔕 Activar'"></span>
                    <span x-show="loading">Procesando...</span>
                </x-filament::button>
            </template>
        </div>
    </x-filament::section>

    @once
        @push('scripts')
            <script>
                function urlBase64ToUint8Array(base64String) {
                    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
                    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
                    const rawData = atob(base64);
                    const outputArray = new Uint8Array(rawData.length);
                    for (let i = 0; i < rawData.length; i++) {
                        outputArray[i] = rawData.charCodeAt(i);
                    }
                    return outputArray;
                }

                document.addEventListener('alpine:init', () => {
                    Alpine.data('pushNotificationsWidget', ({ vapidPublicKey, token }) => ({
                        supported: false,
                        permission: 'default',
                        subscribed: false,
                        loading: false,
                        error: null,

                        async init() {
                            this.supported = 'serviceWorker' in navigator
                                && 'PushManager' in window
                                && 'Notification' in window
                                && !!vapidPublicKey;

                            if (!this.supported) return;

                            this.permission = Notification.permission;

                            try {
                                const registration = await navigator.serviceWorker.register('/push-sw.js');
                                const sub = await registration.pushManager.getSubscription();
                                this.subscribed = !!sub;
                            } catch (e) {
                                this.supported = false;
                            }
                        },

                        async subscribe() {
                            this.loading = true;
                            this.error = null;
                            try {
                                this.permission = await Notification.requestPermission();
                                if (this.permission !== 'granted') {
                                    this.error = 'Permiso de notificaciones denegado';
                                    return;
                                }

                                const registration = await navigator.serviceWorker.ready;
                                const subscription = await registration.pushManager.subscribe({
                                    userVisibleOnly: true,
                                    applicationServerKey: urlBase64ToUint8Array(vapidPublicKey),
                                });
                                const json = subscription.toJSON();

                                await fetch('/api/v1/push-subscriptions', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'Authorization': 'Bearer ' + token,
                                    },
                                    body: JSON.stringify({ endpoint: json.endpoint, keys: json.keys }),
                                });

                                this.subscribed = true;
                            } catch (e) {
                                this.error = 'Error al suscribirse a notificaciones';
                            } finally {
                                this.loading = false;
                            }
                        },

                        async unsubscribe() {
                            this.loading = true;
                            this.error = null;
                            try {
                                const registration = await navigator.serviceWorker.ready;
                                const subscription = await registration.pushManager.getSubscription();

                                if (subscription) {
                                    const endpoint = subscription.endpoint;
                                    await subscription.unsubscribe();

                                    await fetch('/api/v1/push-subscriptions', {
                                        method: 'DELETE',
                                        headers: {
                                            'Content-Type': 'application/json',
                                            'Authorization': 'Bearer ' + token,
                                        },
                                        body: JSON.stringify({ endpoint }),
                                    });
                                }

                                this.subscribed = false;
                            } catch (e) {
                                this.error = 'Error al cancelar la suscripción';
                            } finally {
                                this.loading = false;
                            }
                        },
                    }));
                });
            </script>
        @endpush
    @endonce
</x-filament-widgets::widget>
