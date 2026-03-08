<?php

class PrimbonJawa
{
    /**
     * Neptu hari & pasaran versi primbon umum.
     */
    private static array $neptuHari = [
        'Minggu' => 5,
        'Senin'  => 4,
        'Selasa' => 3,
        'Rabu'   => 7,
        'Kamis'  => 8,
        'Jumat'  => 6,
        'Sabtu'  => 9,
    ];

    private static array $neptuPasaran = [
        'Legi'   => 5,
        'Pahing' => 9,
        'Pon'    => 7,
        'Wage'   => 4,
        'Kliwon' => 8,
    ];

    /**
     * 41 hasil jodoh (dari dump EXE).
     * Index 1..41.
     */
    private static array $hasilJodoh = [
        1  => 'Akeh pangkalane (banyak rintangannya)',
        2  => 'Tulus sandang pangane (sandang pangannya selalu ada)',
        3  => 'Adoh sandang pangane (dijauhi rejeki / susah sandang pangannya)',
        4  => 'Akeh bilahine (banyak celaka / malapetakanya)',
        5  => 'Akeh godane (banyak godaannya)',
        6  => 'Akeh rejekine (banyak rejekinya)',
        7  => 'Akeh rencanane (banyak rencananya / perkara)',
        8  => 'Akeh sambekalane (banyak malapetakanya)',
        9  => 'Anake akeh sing mati (banyak anaknya yang mati)',
        10 => 'Becik (baik)',
        11 => 'Becik kinasihan (baik dan saling mengasihi)',
        12 => 'Cepak rejekine (rejekinya tercukupi)',
        13 => 'Cepak sandang pangane (kebutuhan sandang pangan tercukupi)',
        14 => 'Dadi pangauban (jadi tempat mengadu / berlindung bagi orang-orang yang kesusahan)',
        15 => 'Gedhe bilahine (besar malapetakanya)',
        16 => 'Gelis mati siji (salah satu akan cepat mati)',
        17 => 'Gelis pegat (cepat cerai)',
        18 => 'Gelis sugih (cepat menjadi kaya)',
        19 => 'Giras rejekine (susah rejekinya)',
        20 => 'Ingukum maring rabine (terhukum / terbelenggu oleh pasangannya)',
        21 => 'Kalah siji (salah satu akan kalah / mengalah terus)',
        22 => 'Kasurang-surang (sering sengsara)',
        23 => 'Kerep lara (sering sakit)',
        24 => 'Kinasihan dening wong (banyak orang yang mengasihi)',
        25 => 'Kuat, adoh rejekine (kuat tapi dijauhi rejeki)',
        26 => 'Mlarat (melarat)',
        27 => 'Nemu bilahi saka awake dhewe (celaka karena diri sendiri)',
        28 => 'Oleh nugraha (mendapat anugerah)',
        29 => 'Pegat (cerai)',
        30 => 'Rukun',
        31 => 'Slamet, akeh rejekine (selamat dan banyak rejeki)',
        32 => 'Sugih rejeki (banyak rejeki)',
        33 => 'Sugih satru (banyak musuh)',
        34 => 'Tulus begjane (senantiasa beruntung)',
        35 => 'Tulus palakramane (perjodohannya langgeng)',
        36 => 'Gentho, larang anak (susah punya anak)',
        37 => 'Gembili, sugih anak (banyak anak)',
        38 => 'Sri, sugih rejeki (penuh kemakmuran)',
        39 => 'Punggel, mati siji (salah satu meninggal duluan)',
        40 => 'Kerep lara (sering sakit)',
        41 => 'Sugih lara (banyak penyakit)',
    ];

    /**
     * Hitung weton (hari + pasaran + neptu) dari tanggal 'YYYY-mm-dd'.
     */
    public static function hitungWetonDariTanggal(string $tanggal): array
    {
        $tz   = new DateTimeZone('Asia/Jakarta');
        $date = new DateTime($tanggal, $tz);

        // Hari: Minggu..Sabtu
        $hariIndex = (int) $date->format('w'); // 0=Sunday..6=Saturday
        $namaHari = [
            0 => 'Minggu',
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
        ];
        $hari = $namaHari[$hariIndex];

        // Pasaran: patokan 1900-01-01 = Senin Pahing
        $ref = new DateTime('1900-01-01', $tz);
        $detikSelisih = $date->getTimestamp() - $ref->getTimestamp();
        $selisihHari  = intdiv($detikSelisih, 86400);

        $siklusPasaran = ['Pahing', 'Pon', 'Wage', 'Kliwon', 'Legi'];

        $mod = function (int $a, int $n): int {
            $r = $a % $n;
            return $r < 0 ? $r + $n : $r;
        };

        $idxPasaran = $mod($selisihHari, 5);
        $pasaran    = $siklusPasaran[$idxPasaran];

        $nHari    = self::$neptuHari[$hari] ?? 0;
        $nPasaran = self::$neptuPasaran[$pasaran] ?? 0;
        $nTotal   = $nHari + $nPasaran;

        return [
            'tanggal'       => $date->format('Y-m-d'),
            'hari'          => $hari,
            'pasaran'       => $pasaran,
            'neptu_hari'    => $nHari,
            'neptu_pasaran' => $nPasaran,
            'neptu_total'   => $nTotal,
        ];
    }

    /**
     * Hitung total neptu dua orang dari tanggal lahir masing-masing.
     */
    public static function hitungTotalNeptuPasangan(
        string $tglA,
        string $tglB
    ): array {
        $wetonA = self::hitungWetonDariTanggal($tglA);
        $wetonB = self::hitungWetonDariTanggal($tglB);

        $total = $wetonA['neptu_total'] + $wetonB['neptu_total'];

        return [
            'total_neptu' => $total,
            'wetonA'      => $wetonA,
            'wetonB'      => $wetonB,
        ];
    }

    /**
     * Mapping total neptu → index 1..41.
     *
     * SEKARANG: pakai modulo 41 supaya semua total masuk ke 1..41.
     * NANTI: ketika pola persis EXE ketemu, cukup ubah fungsi ini.
     */
    private static function mapTotalToIndex(int $totalNeptu): int
    {
        $max = count(self::$hasilJodoh); // 41
        return (($totalNeptu - 1) % $max) + 1;
    }

    /**
     * Ramalan jodoh dari dua tanggal lahir (Masehi).
     */
    public static function ramalJodohDariTanggal(
        string $tglA,
        string $tglB
    ): array {
        $data = self::hitungTotalNeptuPasangan($tglA, $tglB);
        $total = $data['total_neptu'];
        $index = self::mapTotalToIndex($total);

        $hasil = self::$hasilJodoh[$index] ?? 'Di luar tabel (cek logika pemetaan total → index)';

        return [
            'wetonA'      => $data['wetonA'],
            'wetonB'      => $data['wetonB'],
            'total_neptu' => $total,
            'index'       => $index,
            'hasil'       => $hasil,
        ];
    }
}

// ========================
// Contoh pemakaian
// ========================

// Contoh: pakai tanggal yang sama dengan output EXE-mu
try {
    $ramal = PrimbonJawa::ramalJodohDariTanggal(
        '1991-02-11', // Senin Kliwon, neptu 12 (harusnya match EXE)
        '2004-03-05'  // Jumat Legi, neptu 11 (harusnya match EXE)
    );

    echo "Pihak Laki-laki:\n";
    echo "  Tanggal : {$ramal['wetonA']['tanggal']}\n";
    echo "  Weton   : {$ramal['wetonA']['hari']} {$ramal['wetonA']['pasaran']}\n";
    echo "  Neptu   : {$ramal['wetonA']['neptu_total']}\n\n";

    echo "Pihak Perempuan:\n";
    echo "  Tanggal : {$ramal['wetonB']['tanggal']}\n";
    echo "  Weton   : {$ramal['wetonB']['hari']} {$ramal['wetonB']['pasaran']}\n";
    echo "  Neptu   : {$ramal['wetonB']['neptu_total']}\n\n";

    echo "Total Neptu Pasangan : {$ramal['total_neptu']}\n";
    echo "Index Tabel          : {$ramal['index']}\n";
    echo "Hasil Primbon        : {$ramal['hasil']}\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

