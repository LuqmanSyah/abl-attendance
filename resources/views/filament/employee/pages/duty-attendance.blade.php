<x-filament-panels::page>
    @php
        $assignments = $this->todayAssignments;

        $formatDistance = fn (?int $distance): string => filled($distance)
            ? number_format($distance).' meter'
            : 'Belum dihitung';

        $formatLocationStatus = fn (?string $status): string => match ($status) {
            'inside_radius' => 'Dalam Radius',
            'outside_radius' => 'Luar Radius',
            default => 'Belum ada data lokasi',
        };

        $formatVerification = fn (?string $status): string => match ($status) {
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            'pending' => 'Menunggu',
            default => '-',
        };

        $verificationColor = fn (?string $status): string => match ($status) {
            'approved' => 'success',
            'rejected' => 'danger',
            'pending' => 'warning',
            default => 'gray',
        };
    @endphp

    <style>
        .duty-page {
            max-width: 980px;
        }

        .duty-list {
            display: grid;
            grid-template-columns: minmax(0, 1fr);
            gap: 1rem;
        }

        .duty-card {
            border: 1px solid color-mix(in srgb, currentColor 11%, transparent);
            border-radius: .625rem;
            overflow: hidden;
            background: color-mix(in srgb, currentColor 2%, transparent);
        }

        .duty-card-header {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-start;
            justify-content: space-between;
            gap: .9rem;
            padding: 1rem 1.1rem;
            border-bottom: 1px solid color-mix(in srgb, currentColor 10%, transparent);
        }

        .duty-title {
            margin: 0;
            font-size: 1.05rem;
            font-weight: 700;
            line-height: 1.35;
        }

        .duty-location {
            margin-top: .25rem;
            opacity: .72;
            font-size: .9rem;
        }

        .duty-badges {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: .45rem;
        }

        .duty-card-body {
            display: grid;
            grid-template-columns: minmax(0, 1fr);
            gap: 1rem;
            padding: 1.1rem;
        }

        .duty-meta-grid,
        .duty-attendance-grid,
        .duty-detail-grid {
            display: grid;
            gap: .75rem;
        }

        .duty-meta-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .duty-attendance-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .duty-detail-grid {
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }

        .duty-meta,
        .duty-attendance-card,
        .duty-detail {
            border: 1px solid color-mix(in srgb, currentColor 10%, transparent);
            border-radius: .5rem;
            padding: .85rem;
        }

        .duty-label {
            opacity: .64;
            font-size: .76rem;
            font-weight: 650;
            line-height: 1.2;
            text-transform: uppercase;
        }

        .duty-value {
            margin-top: .35rem;
            font-size: .95rem;
            font-weight: 650;
            line-height: 1.35;
        }

        .duty-time {
            margin-top: .35rem;
            font-size: 1.55rem;
            font-weight: 750;
            line-height: 1.15;
        }

        .duty-note {
            margin-top: .35rem;
            opacity: .72;
            font-size: .84rem;
            line-height: 1.35;
        }

        .duty-actions {
            display: flex;
            flex-wrap: wrap;
            gap: .65rem;
        }

        .duty-alert,
        .duty-empty {
            border: 1px solid color-mix(in srgb, currentColor 12%, transparent);
            border-radius: .5rem;
            padding: .8rem .95rem;
            font-size: .9rem;
            line-height: 1.5;
        }

        .duty-alert {
            color: rgb(250 204 21);
            background: color-mix(in srgb, currentColor 4%, transparent);
        }

        .duty-empty {
            opacity: .78;
        }

        @media (max-width: 760px) {
            .duty-card-header {
                align-items: stretch;
            }

            .duty-badges {
                justify-content: flex-start;
            }

            .duty-meta-grid,
            .duty-attendance-grid,
            .duty-detail-grid {
                grid-template-columns: minmax(0, 1fr);
            }

            .duty-actions {
                flex-direction: column;
            }

            .duty-actions .fi-btn {
                justify-content: center;
                width: 100%;
            }
        }
    </style>

    <div
        x-data="{
            loadingAction: null,
            locate(action, assignmentId) {
                if (! navigator.geolocation) {
                    alert('Browser tidak mendukung GPS.');
                    return;
                }

                this.loadingAction = `${action}-${assignmentId}`;

                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        $wire[action](
                            assignmentId,
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
        class="duty-page"
    >
        <div class="duty-list">
            @forelse ($assignments as $assignment)
                @php
                    $record = $assignment->attendanceRecords->first();
                    $now = now();
                    $assignmentIsActive = $assignment->status === 'active'
                        && $assignment->starts_at->lte($now)
                        && $assignment->ends_at->gte($now);

                    $assignmentStatus = match (true) {
                        $assignment->status === 'cancelled' => 'Dibatalkan',
                        $assignment->status === 'completed' => 'Selesai',
                        $assignment->starts_at->gt($now) => 'Belum mulai',
                        $assignment->ends_at->lt($now) => 'Sudah lewat',
                        $assignmentIsActive => 'Aktif',
                        default => 'Tidak aktif',
                    };

                    $assignmentStatusColor = match ($assignmentStatus) {
                        'Aktif' => 'success',
                        'Belum mulai' => 'warning',
                        'Sudah lewat', 'Selesai' => 'gray',
                        'Dibatalkan' => 'danger',
                        default => 'gray',
                    };

                    $attendanceStatus = ! $record
                        ? 'Belum absen'
                        : ($record->check_out_at ? 'Selesai' : 'Sudah masuk');

                    $attendanceStatusColor = ! $record
                        ? 'gray'
                        : ($record->check_out_at ? 'success' : 'warning');
                @endphp

                <section class="duty-card">
                    <div class="duty-card-header">
                        <div>
                            <h2 class="duty-title">{{ $assignment->title }}</h2>
                            <div class="duty-location">{{ $assignment->location_name }}</div>
                        </div>

                        <div class="duty-badges">
                            <x-filament::badge :color="$assignmentStatusColor">
                                {{ $assignmentStatus }}
                            </x-filament::badge>

                            <x-filament::badge :color="$attendanceStatusColor">
                                {{ $attendanceStatus }}
                            </x-filament::badge>

                            @if ($record)
                                <x-filament::badge :color="$verificationColor($record->verification_status)">
                                    {{ $formatVerification($record->verification_status) }}
                                </x-filament::badge>
                            @endif
                        </div>
                    </div>

                    <div class="duty-card-body">
                        @unless ($assignmentIsActive)
                            <div class="duty-alert">
                                Absensi dinas belum dapat dilakukan karena status saat ini: {{ $assignmentStatus }}.
                            </div>
                        @endunless

                        <div class="duty-meta-grid">
                            <div class="duty-meta">
                                <div class="duty-label">Mulai</div>
                                <div class="duty-value">{{ $assignment->starts_at->format('d M Y H:i') }}</div>
                            </div>

                            <div class="duty-meta">
                                <div class="duty-label">Selesai</div>
                                <div class="duty-value">{{ $assignment->ends_at->format('d M Y H:i') }}</div>
                            </div>

                            <div class="duty-meta">
                                <div class="duty-label">Radius</div>
                                <div class="duty-value">{{ number_format($assignment->radius_meters) }} meter</div>
                            </div>
                        </div>

                        <div class="duty-attendance-grid">
                            <div class="duty-attendance-card">
                                <div class="duty-label">Masuk</div>
                                <div class="duty-time">{{ $record?->check_in_at?->format('H:i') ?? '--:--' }}</div>
                                <div class="duty-note">
                                    {{ $record ? $formatLocationStatus($record->check_in_location_status) : 'Belum absen masuk' }}
                                </div>
                            </div>

                            <div class="duty-attendance-card">
                                <div class="duty-label">Pulang</div>
                                <div class="duty-time">{{ $record?->check_out_at?->format('H:i') ?? '--:--' }}</div>
                                <div class="duty-note">
                                    {{ $record?->check_out_at ? $formatLocationStatus($record->check_out_location_status) : 'Belum absen pulang' }}
                                </div>
                            </div>
                        </div>

                        <div class="duty-actions">
                            <x-filament::button
                                :disabled="! $assignmentIsActive || filled($record?->check_in_at)"
                                :icon="\Filament\Support\Icons\Heroicon::ArrowRightOnRectangle"
                                x-bind:disabled="loadingAction !== null || {{ $assignmentIsActive ? 'false' : 'true' }}"
                                x-on:click="locate('checkIn', {{ $assignment->id }})"
                            >
                                <span x-show="loadingAction !== 'checkIn-{{ $assignment->id }}'">Absen Masuk</span>
                                <span x-cloak x-show="loadingAction === 'checkIn-{{ $assignment->id }}'">Mengambil GPS...</span>
                            </x-filament::button>

                            <x-filament::button
                                color="gray"
                                :disabled="! $assignmentIsActive || blank($record?->check_in_at) || filled($record?->check_out_at)"
                                :icon="\Filament\Support\Icons\Heroicon::ArrowLeftOnRectangle"
                                x-bind:disabled="loadingAction !== null || {{ $assignmentIsActive ? 'false' : 'true' }}"
                                x-on:click="locate('checkOut', {{ $assignment->id }})"
                            >
                                <span x-show="loadingAction !== 'checkOut-{{ $assignment->id }}'">Absen Pulang</span>
                                <span x-cloak x-show="loadingAction === 'checkOut-{{ $assignment->id }}'">Mengambil GPS...</span>
                            </x-filament::button>
                        </div>

                        @if ($record)
                            <div class="duty-detail-grid">
                                <div class="duty-detail">
                                    <div class="duty-label">Jarak Masuk</div>
                                    <div class="duty-value">{{ $formatDistance($record->check_in_distance_meters) }}</div>
                                </div>

                                <div class="duty-detail">
                                    <div class="duty-label">Lokasi Masuk</div>
                                    <div class="duty-value">{{ $formatLocationStatus($record->check_in_location_status) }}</div>
                                </div>

                                <div class="duty-detail">
                                    <div class="duty-label">Jarak Pulang</div>
                                    <div class="duty-value">{{ $formatDistance($record->check_out_distance_meters) }}</div>
                                </div>

                                <div class="duty-detail">
                                    <div class="duty-label">Lokasi Pulang</div>
                                    <div class="duty-value">{{ $formatLocationStatus($record->check_out_location_status) }}</div>
                                </div>
                            </div>
                        @endif
                    </div>
                </section>
            @empty
                <div class="duty-empty">
                    Tidak ada penugasan dinas untuk tanggal ini.
                </div>
            @endforelse
        </div>
    </div>
</x-filament-panels::page>
