/**
 * Ravelin Payment Mixin
 * Adds the Ravelin device ID to the payment method additional data.
 */
define([], function () {
    'use strict';

    return function (Component) {
        return Component.extend({
            getData: function () {
                var data = this._super(),
                    deviceId = window.ravelinDeviceId || null;

                if (!deviceId) {
                    return data;
                }

                data.additional_data = data.additional_data || {};
                data.additional_data.ravelin_device_id = deviceId;

                return data;
            }
        });
    };
});
