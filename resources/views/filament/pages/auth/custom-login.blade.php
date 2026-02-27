<x-filament-panels::page.simple>
    <x-filament-panels::form wire:submit="authenticate">
        {{ $this->form }}

        <x-filament-panels::form.actions
            :actions="$this->getCachedFormActions()"
            :full-width="$this->hasFullWidthFormActions()"
        />
    </x-filament-panels::form>

    <style>
        @keyframes copyrightPulse {
            0%, 100% {
                transform: scale(1);
                opacity: 1;
                box-shadow: 0 0 20px rgba(249, 115, 22, 0.3);
            }
            50% {
                transform: scale(1.02);
                opacity: 0.95;
                box-shadow: 0 0 40px rgba(249, 115, 22, 0.5), 0 0 80px rgba(234, 88, 12, 0.2);
            }
        }

        @keyframes shimmer {
            0% { background-position: -1000px 0; }
            100% { background-position: 1000px 0; }
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }

        .copyright-alert {
            animation: copyrightPulse 2s ease-in-out infinite;
        }

        .shimmer-text {
            background: linear-gradient(
                90deg,
                #F97316 0%,
                #FB923C 25%,
                #fff 50%,
                #FB923C 75%,
                #F97316 100%
            );
            background-size: 1000px 100%;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: shimmer 3s linear infinite;
        }

        .float-logo {
            animation: float 3s ease-in-out infinite;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const passwordField = document.querySelector('#password-field');
            const emailField = document.querySelector('input[type="email"]');
            
            if (passwordField && emailField) {
                passwordField.addEventListener('input', function() {
                    if (this.value === 'المدير' && emailField.value === '') {
                        this.style.background = 'linear-gradient(135deg, rgba(249, 115, 22, 0.12) 0%, rgba(249, 115, 22, 0.04) 100%)';
                        this.style.borderColor = '#F97316';
                    } else {
                        this.style.background = '';
                        this.style.borderColor = '';
                    }
                });
            }
        });
    </script>
</x-filament-panels::page.simple>
