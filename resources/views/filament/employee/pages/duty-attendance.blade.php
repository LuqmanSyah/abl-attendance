<x-filament-panels::page>
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
        class="space-y-4"
    >
        @forelse ($this->activeAssignments as $assignment)
            @php
                $record = $assignment->attendanceRecords->first();
            @endphp

            <section class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                    <div class="space-y-2">
                        <div>
                            <h2 class="text-base font-semibold text-gray-950">{{ $assignment->title }}</h2>
                            <p class="text-sm text-gray-600">{{ $assignment->location_name }}</p>
                        </div>

                        <dl class="grid gap-2 text-sm text-gray-700 md:grid-cols-2">
                            <div>
                                <dt class="font-medium text-gray-950">Mulai</dt>
                                <dd>{{ $assignment->starts_at->format('d M Y H:i') }}</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-gray-950">Selesai</dt>
                                <dd>{{ $assignment->ends_at->format('d M Y H:i') }}</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-gray-950">Radius</dt>
                                <dd>{{ number_format($assignment->radius_meters) }} meter</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-gray-950">Status Absensi</dt>
                                <dd>
                                    @if (! $record)
                                        Belum absen
                                    @elseif (! $record->check_out_at)
                                        Sudah masuk
                                    @else
                                        Selesai
                                    @endif
                                </dd>
                            </div>
                        </dl>
                    </div>

                    <div class="flex min-w-48 flex-col gap-2">
                        <button
                            type="button"
                            x-on:click="locate('checkIn', {{ $assignment->id }})"
                            x-bind:disabled="loadingAction !== null"
                            @class([
                                'fi-btn fi-size-md fi-btn-color-primary',
                                'opacity-50' => $record?->check_in_at,
                            ])
                            @disabled($record?->check_in_at)
                        >
                            <span x-show="loadingAction !== 'checkIn-{{ $assignment->id }}'">Absen Masuk</span>
                            <span x-show="loadingAction === 'checkIn-{{ $assignment->id }}'">Mengambil GPS...</span>
                        </button>

                        <button
                            type="button"
                            x-on:click="locate('checkOut', {{ $assignment->id }})"
                            x-bind:disabled="loadingAction !== null"
                            @class([
                                'fi-btn fi-size-md fi-btn-color-gray',
                                'opacity-50' => ! $record?->check_in_at || $record?->check_out_at,
                            ])
                            @disabled(! $record?->check_in_at || $record?->check_out_at)
                        >
                            <span x-show="loadingAction !== 'checkOut-{{ $assignment->id }}'">Absen Pulang</span>
                            <span x-show="loadingAction === 'checkOut-{{ $assignment->id }}'">Mengambil GPS...</span>
                        </button>
                    </div>
                </div>

                @if ($record)
                    <div class="mt-4 grid gap-3 border-t border-gray-100 pt-4 text-sm md:grid-cols-3">
                        <div>
                            <p class="font-medium text-gray-950">Jarak Masuk</p>
                            <p class="text-gray-700">{{ number_format($record->check_in_distance_meters) }} meter</p>
                        </div>
                        <div>
                            <p class="font-medium text-gray-950">Lokasi Masuk</p>
                            <p class="text-gray-700">
                                {{ $record->check_in_location_status === 'inside_radius' ? 'Dalam Radius' : 'Luar Radius' }}
                            </p>
                        </div>
                        <div>
                            <p class="font-medium text-gray-950">Verifikasi</p>
                            <p class="text-gray-700">
                                @switch($record->verification_status)
                                    @case('approved')
                                        Disetujui
                                        @break
                                    @case('rejected')
                                        Ditolak
                                        @break
                                    @default
                                        Menunggu
                                @endswitch
                            </p>
                        </div>
                    </div>
                @endif
            </section>
        @empty
            <section class="rounded-lg border border-gray-200 bg-white p-6 text-sm text-gray-600 shadow-sm">
                Tidak ada penugasan dinas aktif untuk saat ini.
            </section>
        @endforelse
    </div>
</x-filament-panels::page>
