<x-filament-panels::page>
    @php
        $record = $this->todayRecord;
        $activeDutyAssignment = $this->activeDutyAssignment;
        $isOnDuty = filled($activeDutyAssignment);

        $attendanceStatus = ! $record
            ? 'Belum absen'
            : ($record->check_out_at ? 'Selesai' : 'Sudah masuk');

        $attendanceStatusColor = ! $record
            ? 'gray'
            : ($record->check_out_at ? 'success' : 'warning');

        $verificationLabel = match ($record?->verification_status) {
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            'pending' => 'Menunggu',
            default => '-',
        };

        $verificationColor = match ($record?->verification_status) {
            'approved' => 'success',
            'rejected' => 'danger',
            'pending' => 'warning',
            default => 'gray',
        };

        $officeLocationConfigured = is_numeric(config('attendance.office.latitude'))
            && is_numeric(config('attendance.office.longitude'));
        $canUseOfficeAttendance = $officeLocationConfigured && ! $isOnDuty;

        $formatLocationStatus = function (?string $status) use ($officeLocationConfigured): string {
            return match ($status) {
                'inside_radius' => 'Dalam Radius',
                'outside_radius' => 'Luar Radius',
                default => $officeLocationConfigured ? 'Belum ada data lokasi' : 'Titik kantor belum diatur',
            };
        };

        $formatDistance = fn (?int $distance): string => filled($distance)
            ? number_format($distance).' meter'
            : ($officeLocationConfigured ? 'Belum dihitung' : 'Atur titik kantor');
    @endphp

    <style>
        .attendance-page {
            max-width: 920px;
        }

        .attendance-summary {
            display: grid;
            grid-template-columns: minmax(0, 1fr);
            gap: 1rem;
        }

        .attendance-header {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
        }

        .attendance-title {
            margin: 0;
            font-size: 1.05rem;
            font-weight: 650;
            line-height: 1.4;
        }

        .attendance-date {
            margin-top: .25rem;
            opacity: .72;
            font-size: .9rem;
        }

        .attendance-badges {
            display: flex;
            flex-wrap: wrap;
            gap: .5rem;
        }

        .attendance-actions {
            display: flex;
            flex-wrap: wrap;
            gap: .75rem;
        }

        .attendance-alert {
            border: 1px solid color-mix(in srgb, currentColor 14%, transparent);
            border-radius: .5rem;
            padding: .75rem 1rem;
            color: rgb(250 204 21);
            background: color-mix(in srgb, currentColor 4%, transparent);
            font-size: .9rem;
            line-height: 1.5;
        }

        .attendance-alert a {
            font-weight: 650;
            text-decoration: underline;
            text-underline-offset: .2em;
        }

        .attendance-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .875rem;
        }

        .attendance-card {
            border: 1px solid color-mix(in srgb, currentColor 12%, transparent);
            border-radius: .625rem;
            padding: 1rem;
        }

        .attendance-card-label {
            display: flex;
            align-items: center;
            gap: .5rem;
            margin-bottom: .45rem;
            opacity: .72;
            font-size: .85rem;
            font-weight: 600;
        }

        .attendance-card-value {
            font-size: 1.65rem;
            font-weight: 700;
            line-height: 1.15;
        }

        .attendance-card-note {
            margin-top: .35rem;
            opacity: .7;
            font-size: .85rem;
        }

        .attendance-details {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: .75rem;
        }

        .attendance-detail {
            border-top: 1px solid color-mix(in srgb, currentColor 10%, transparent);
            padding-top: .75rem;
        }

        .attendance-detail-label {
            opacity: .65;
            font-size: .78rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .attendance-detail-value {
            margin-top: .2rem;
            font-size: .95rem;
            font-weight: 600;
        }

        @media (max-width: 760px) {
            .attendance-grid,
            .attendance-details {
                grid-template-columns: minmax(0, 1fr);
            }

            .attendance-actions {
                flex-direction: column;
            }

            .attendance-actions .fi-btn {
                justify-content: center;
                width: 100%;
            }
        }
    </style>

    <div
        x-data="{
            loadingAction: null,
            locate(action) {
                if (! navigator.geolocation) {
                    alert('Browser tidak mendukung GPS.');
                    return;
                }

                this.loadingAction = action;

                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        $wire[action](
                            position.coords.latitude,
                            position.coords.longitude,
                            position.coords.accuracy,
                        ).then(() => {
                            this.loadingAction = null;
                        });
                    },
                    () => {
                        this.loadingAction = null;
                        alert('Lokasi tidak dapat diambil. Pastikan izin lokasi browser aktif.');
                    },
                    {
                        enableHighAccuracy: true,
                        maximumAge: 0,
                        timeout: 15000,
                    },
                );
            },
        }"
        class="attendance-page"
    >
        <x-filament::section
            :icon="\Filament\Support\Icons\Heroicon::OutlinedBuildingOffice"
            icon-color="primary"
        >
            <div class="attendance-summary">
                @if ($isOnDuty)
                    <div class="attendance-alert">
                        Anda sedang memiliki penugasan dinas aktif: {{ $activeDutyAssignment->title }} di {{ $activeDutyAssignment->location_name }}.
                        <a href="{{ url('/pegawai/absensi-dinas') }}">Buka Absensi Dinas</a>.
                    </div>
                @elseif (! $officeLocationConfigured)
                    <div class="attendance-alert">
                        Koordinat kantor belum diatur. Isi ATTENDANCE_OFFICE_LATITUDE dan ATTENDANCE_OFFICE_LONGITUDE agar lokasi dan jarak absensi bisa dihitung.
                    </div>
                @endif

                <div class="attendance-header">
                    <div>
                        <h2 class="attendance-title">Absensi Hari Ini</h2>
                        <div class="attendance-date">{{ now()->format('d M Y') }}</div>
                    </div>

                    <div class="attendance-badges">
                        <x-filament::badge :color="$attendanceStatusColor">
                            {{ $attendanceStatus }}
                        </x-filament::badge>

                        <x-filament::badge :color="$verificationColor">
                            {{ $verificationLabel }}
                        </x-filament::badge>
                    </div>
                </div>

                <div class="attendance-grid">
                    <div class="attendance-card">
                        <div class="attendance-card-label">
                            Masuk
                        </div>
                        <div class="attendance-card-value">
                            {{ $record?->check_in_at?->format('H:i') ?? '--:--' }}
                        </div>
                        <div class="attendance-card-note">
                            {{ $record ? $formatLocationStatus($record->check_in_location_status) : 'Belum absen' }}
                        </div>
                    </div>

                    <div class="attendance-card">
                        <div class="attendance-card-label">
                            Pulang
                        </div>
                        <div class="attendance-card-value">
                            {{ $record?->check_out_at?->format('H:i') ?? '--:--' }}
                        </div>
                        <div class="attendance-card-note">
                            {{ $record?->check_out_at ? $formatLocationStatus($record->check_out_location_status) : 'Belum absen pulang' }}
                        </div>
                    </div>
                </div>

                <div class="attendance-actions">
                    <x-filament::button
                        :disabled="! $canUseOfficeAttendance || filled($record?->check_in_at)"
                        :icon="\Filament\Support\Icons\Heroicon::ArrowRightOnRectangle"
                        x-bind:disabled="loadingAction !== null || {{ $canUseOfficeAttendance ? 'false' : 'true' }}"
                        x-on:click="locate('checkIn')"
                    >
                        <span x-show="loadingAction !== 'checkIn'">Absen Masuk</span>
                        <span x-cloak x-show="loadingAction === 'checkIn'">Mengambil GPS...</span>
                    </x-filament::button>

                    <x-filament::button
                        color="gray"
                        :disabled="! $canUseOfficeAttendance || blank($record?->check_in_at) || filled($record?->check_out_at)"
                        :icon="\Filament\Support\Icons\Heroicon::ArrowLeftOnRectangle"
                        x-bind:disabled="loadingAction !== null || {{ $canUseOfficeAttendance ? 'false' : 'true' }}"
                        x-on:click="locate('checkOut')"
                    >
                        <span x-show="loadingAction !== 'checkOut'">Absen Pulang</span>
                        <span x-cloak x-show="loadingAction === 'checkOut'">Mengambil GPS...</span>
                    </x-filament::button>
                </div>

                @if ($record)
                    <div class="attendance-details">
                        <div class="attendance-detail">
                            <div class="attendance-detail-label">Lokasi Masuk</div>
                            <div class="attendance-detail-value">{{ $formatLocationStatus($record->check_in_location_status) }}</div>
                        </div>

                        <div class="attendance-detail">
                            <div class="attendance-detail-label">Jarak Masuk</div>
                            <div class="attendance-detail-value">{{ $formatDistance($record->check_in_distance_meters) }}</div>
                        </div>

                        <div class="attendance-detail">
                            <div class="attendance-detail-label">Lokasi Pulang</div>
                            <div class="attendance-detail-value">{{ $formatLocationStatus($record->check_out_location_status) }}</div>
                        </div>

                        <div class="attendance-detail">
                            <div class="attendance-detail-label">Jarak Pulang</div>
                            <div class="attendance-detail-value">{{ $formatDistance($record->check_out_distance_meters) }}</div>
                        </div>
                    </div>
                @endif
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
