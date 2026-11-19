/**
 * Ravelin FraudSight Integration
 * Simplified implementation that handles SDK loading, session management, and payment mixin
 */
define(
    [
        'jquery',
        'uiComponent',
        'Magento_Checkout/js/model/quote',
        'ravelin' // Ensure the Ravelin SDK is loaded
    ],
    function ($, Component, quote, Ravelin) {
        'use strict';

        return Component.extend({
            initialize: function () {
                this._super();
                this.loadAndInitRavelin();
                return this;
            },

            loadAndInitRavelin: function () {
                let config = window.checkoutConfig.ravelinConfig || {};
                console.log('[Ravelin] Config:', config);

                if (!config.enabled) {
                    console.warn('[Ravelin] Integration is disabled');
                    return;
                }

                if (!config.publicKey ) {
                    console.warn('[Ravelin] Missing public key or base URL');
                    return;
                }

                var ravelin = new Ravelin({
                    key: config.publicKey
                });

                ravelin.core.id()
                    .then(function (deviceId) {
                        console.log('[Ravelin] Device ID:', deviceId);

                        window.ravelinDeviceId = deviceId;
                    })
                    .catch(function (error) {
                        console.error('[Ravelin] Failed to get device ID:', error);
                    });
            },
        });
    }
);
