<x-filament-panels::page>
    <x-filament::section
        :icon="\Filament\Support\Icons\Heroicon::OutlinedBuildingOffice"
        icon-color="primary"
    >
        <x-slot name="heading">
            Titik Kantor
        </x-slot>

        <x-slot name="description">
            Atur koordinat dan radius yang dipakai untuk validasi Absensi Kantor pegawai.
        </x-slot>

        <form wire:submit="save" class="grid gap-6">
            {{ $this->form }}

            <div class="rounded-lg border border-yellow-500/30 bg-yellow-500/10 p-4 text-sm text-yellow-500">
                Tips: cari alamat kantor di kolom peta, klik titik lokasi, atau gunakan tombol lokasi saat ini. Latitude dan longitude tetap bisa diisi manual jika diperlukan.
            </div>

            <div class="flex justify-end">
                <x-filament::button type="submit">
                    Simpan Pengaturan
                </x-filament::button>
            </div>
        </form>
    </x-filament::section>
</x-filament-panels::page>
