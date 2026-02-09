{{--
    SARH v1.9.0 — Geolocation Helper Script
    يُحقن فقط في بوابة الموظفين /app عبر renderHook في AppPanelProvider.
    يحل مشكلة Permissions-Policy التي ظهرت في v1.8.x.
--}}
<script>
    window.SarhGeo = {
        /**
         * الحصول على الموقع الجغرافي الحالي.
         * يُستخدم من Livewire components مثل: SarhGeo.getCurrentPosition().then(pos => ...)
         *
         * @returns {Promise<{latitude: number, longitude: number, accuracy: number}>}
         */
        getCurrentPosition: function () {
            return new Promise(function (resolve, reject) {
                if (!navigator.geolocation) {
                    reject(new Error('المتصفح لا يدعم تحديد الموقع الجغرافي.'));
                    return;
                }

                navigator.geolocation.getCurrentPosition(
                    function (position) {
                        resolve({
                            latitude: position.coords.latitude,
                            longitude: position.coords.longitude,
                            accuracy: position.coords.accuracy,
                        });
                    },
                    function (error) {
                        var messages = {
                            1: 'تم رفض إذن تحديد الموقع. يرجى السماح بالوصول من إعدادات المتصفح.',
                            2: 'تعذر تحديد الموقع. تأكد من تفعيل GPS.',
                            3: 'انتهت مهلة تحديد الموقع. حاول مرة أخرى.',
                        };
                        reject(new Error(messages[error.code] || 'خطأ غير معروف في تحديد الموقع.'));
                    },
                    {
                        enableHighAccuracy: true,
                        timeout: 15000,
                        maximumAge: 60000,
                    }
                );
            });
        },

        /**
         * ملء حقول Latitude/Longitude في Filament form تلقائياً.
         * @param {string} latField - اسم حقل خط العرض
         * @param {string} lngField - اسم حقل خط الطول
         */
        fillFormFields: function (latField, lngField) {
            this.getCurrentPosition().then(function (pos) {
                var latInput = document.querySelector('[wire\\:model$="' + latField + '"]') ||
                               document.querySelector('input[name="' + latField + '"]');
                var lngInput = document.querySelector('[wire\\:model$="' + lngField + '"]') ||
                               document.querySelector('input[name="' + lngField + '"]');

                if (latInput) {
                    latInput.value = pos.latitude;
                    latInput.dispatchEvent(new Event('input', { bubbles: true }));
                }
                if (lngInput) {
                    lngInput.value = pos.longitude;
                    lngInput.dispatchEvent(new Event('input', { bubbles: true }));
                }
            }).catch(function (err) {
                console.error('[SarhGeo]', err.message);
            });
        }
    };
</script>
