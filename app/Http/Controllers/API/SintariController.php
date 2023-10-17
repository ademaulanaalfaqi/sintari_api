<?php

namespace App\Http\Controllers\API;

use App\Models\Libnas;
use App\Models\Pegawai;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Pengurangan;
use App\Models\Realisasi;
use Illuminate\Support\Facades\Auth;

class SintariController extends Controller
{
    public function index()
    {

        $user = Auth::user();
        $user = Pegawai::where('pegawai_id', $user->pegawai_id)->first();
        // $pegawaiPertama = $user->first();
        $tahun = date('Y');
        $bulan = date('07');

        $tanggal_end = cal_days_in_month(CAL_GREGORIAN, $bulan, $tahun);;

        $tgl_awal = date('Y-m-d', strtotime($tahun . "-" . $bulan . "-" . "1"));
        $tgl_akhir = date('Y-m-d', strtotime($tahun . "-" . $bulan . "-" . $tanggal_end));
        $awal = strtotime($tgl_awal);
        $akhir = strtotime($tgl_akhir);

        //libur nasional
        $liburnas = Libnas::where('hari_kerja', '1')
            ->whereMonth('libur_mulai', date('07')) //bulan ny ganti suai kebutuhan 'm'
            ->whereYear('libur_mulai', date('Y')) //tahun ny ganti suai kebutuhan
            ->count();

        if ($user->pegawai_absensi == '2' or $user->pegawai_absensi == '3' or $user->pegawai_absensi == '4'   or $user->pegawai_opd_gabung == '578') {
            $hari_kerja = 0;
            for ($i = $awal; $i <= $akhir; $i += (60 * 60 * 24)) {
                // $i_date = date("Y-m-d", $i);
                if (date("N", $i) != "7") {
                    $hari_kerja++;
                }
            }
        }
        $hari_kerja = 0;
        for ($i = $awal; $i <= $akhir; $i += (60 * 60 * 24)) {
            // $i_date = date("Y-m-d", $i);
            if (date("N", $i) != "7"  and date("N", $i) != "6") {
                $hari_kerja++;
            }
        }

        //jumlah absen per bulan
        $hk = DB::table('sintari_kalender_561')
            ->select('kalender_tanggal', DB::raw('COUNT(*) as jumlah'))
            ->where('kalender_pegawai', $user->pegawai_id)
            ->whereMonth('kalender_tanggalmulai', date('07')) //bulan ny ganti suai kebutuhan 'm'
            ->whereYear('kalender_tanggalmulai', date('Y')) //tahun ny ganti suai kebutuhan
            ->groupBy('kalender_tanggal')
            ->orderBy('kalender_tanggal', 'ASC')
            ->get();

        $absnjumlh = count($hk);

        //jumlah absen per hari
        $jumlahabsenharian = DB::table('sintari_kalender_561')
            ->select('kalender_tanggal', DB::raw('COUNT(*) as jumlah'))
            ->where('kalender_pegawai', $user->pegawai_id)
            ->whereBetween('kalender_tanggalmulai', [date('Y-07-01'), date('Y-07-15')])
            ->groupBy('kalender_tanggal')
            ->orderBy('kalender_tanggal', 'ASC')
            ->get();

        $a = round(((count($hk) + $liburnas) * 100) / $hari_kerja, 2);

        $b = round(((count($jumlahabsenharian) + $liburnas) * 100) / $hari_kerja, 2);

        //pemotongan persen per bulan
        $pengurangan = Pengurangan::where('pengurangan_pegawai', $user->pegawai_id)
            ->whereMonth('pengurangan_tanggal', date('07')) //bulan ny ganti suai kebutuhan 'm'
            ->whereYear('pengurangan_tanggal', date('Y')) //tahun ny ganti suai kebutuhan
            ->sum('pengurangan_besar');

        $penguranganpersenharian = Pengurangan::where('pengurangan_pegawai', $user->pegawai_id)
            // ->whereMonth('pengurangan_tanggal', date('07')) //bulan ny ganti suai kebutuhan 'm'
            // ->whereYear('pengurangan_tanggal', date('Y')) //tahun ny ganti suai kebutuhan
            // ->whereDay('pengurangan_tanggal', date('15'))
            ->whereBetween('pengurangan_tanggal', [date('Y-07-01'), date('Y-07-15')])
            ->sum('pengurangan_besar'); // Mengambil data pertama yang cocok

        //absen persen harian
        $persentaseabsenharian = $b - $penguranganpersenharian;

        $RupiahAbsenAwal = round(($persentaseabsenharian / 100) * $user->pegawai_tpp,2);

        $RupiahAbsenAkhir = round(($RupiahAbsenAwal * $persentaseabsenharian) / 100,2);

        //absen persen bulan
        $persentaseabsen = $a - $pengurangan;

        $tunjangan = round((40 / 100) * $user->pegawai_tpp, 2);
        //hasil akhir //absen rupiah bulanan
        $tunjanganasli = round(($tunjangan * $persentaseabsen) / 100, 2);

        $jumenitkinerja = Realisasi::where('realisasi_user', $user->pegawai_id)
            ->whereMonth('realisasi_tanggal', date('07')) //bulan ny ganti suai kebutuhan 'm'
            ->whereYear('realisasi_tanggal', date('Y')) //tahun ny ganti suai kebutuhan
            ->sum('realisasi_waktu');

        //menit kinerja harian
        $jumenitkinerjaharian = Realisasi::where('realisasi_user', $user->pegawai_id)
            ->whereBetween('realisasi_tanggal', [date('Y-07-01'), date('Y-07-15')])
            ->sum('realisasi_waktu');

        $menitkerjafull = 6750;

        //persen kinerja perhari
        $persenkinerjaharian = round(($jumenitkinerjaharian  * 100) / $menitkerjafull, 2);

        $Rupiahkinerjahari = round(($persenkinerjaharian / 100) * $user->pegawai_tpp, 2);

        //kinerja rupiah harian
        $RupiahKinerjaHarian = round(($Rupiahkinerjahari * $persentaseabsenharian) / 100, 2);

        $menitlibnas = Libnas::whereMonth('libur_mulai', date('07')) //bulan ny ganti suai kebutuhan 'm'
            ->whereYear('libur_mulai', date('Y'))
            ->sum('libur_menitpengurangan');

        //kinerja menit
        $jumenitkinerjasli = $jumenitkinerja + $menitlibnas;

        //hasil akhir //kinerja persen bulanan
        $persentasekinerja = round(($jumenitkinerjasli  / 6750) * 100, 2);

        //tppTotal
        $tppTotal = ($tunjanganasli + $persentasekinerja);

        //pemotongan jumlah
        $pemotongan = Pengurangan::where('pengurangan_pegawai', $user->pegawai_id)
            ->whereBetween('pengurangan_tanggal', [date('Y-07-01'), date('Y-07-15')])
            ->get('pengurangan_besar')->count();

        //pemotongan rupiah perhari
        $pemotonganrupiah = round(($penguranganpersenharian / 100) * $user->pegawai_tpp ,2);
        $pemotonganRupiahHari = round(($pemotonganrupiah * $persentaseabsenharian) / 100,2);
        //1 bulan lalu
        $MonthAgo = strtotime("-1 month");
        $MonthAgooo = date('6', $MonthAgo); //ganti format suai kebutuhan

        //tambahan
        $kinerjaBulanLalu = DB::table('sintari_realisasi_' . $MonthAgooo)
            ->where('realisasi_user', $user->pegawai_id)
            ->whereMonth('realisasi_tanggal', date($MonthAgooo)) //bulan ny ganti suai kebutuhan 'm'
            ->whereYear('realisasi_tanggal', date('Y')) //tahun ny ganti suai kebutuhan
            ->sum('realisasi_waktu');;

        // tambahan jumlah
        $tambahan = ($kinerjaBulanLalu - 6750);

        //tambahan persen
        $tambahanPersen = round(($tambahan * 100) / 6750, 2);

        //tambah rupiah
        $tambahanRupiah = round(($tambahanPersen / 100) * $user->pegawai_tpp, 2);
        $tambahanRupiahAkhir = round(($tambahanRupiah / 100),2);

        return response()->json([
            'status' => true,
            'message' => 'Data Ditemukan',
            'id' => $user->pegawai_id,
            'tpp' => $tppTotal,
            'absensi_rupiah' => $RupiahAbsenAkhir,
            'absensi_persen' => $persentaseabsenharian,
            'absensi_jumlah' => $absnjumlh,
            'kinerja_rupiah' => $RupiahKinerjaHarian,
            'kinerja_persen' => $persenkinerjaharian,
            'kinerja_menit' => $jumenitkinerjaharian,
            'pemotongan_rupiah' => $pemotonganRupiahHari,
            'pemotongan_persen' => $penguranganpersenharian,
            'pemotongan_jumlah' => $pemotongan,
            'tambahan_rupiah' => $tambahanRupiahAkhir,
            'tambahan_persen' => $tambahanPersen,
            'tambahan_jumlah' => $tambahan,
            'akun_foto' => $user->pegawai_gambar,
            'akun_nama' => $user->nama,
            'akun_nip' => $user->nip,
            'akun_pangkat' => $user->pegawai_pangkat,
            'akun_golongan' => $user->pegawai_golongan,
            'akun_jabatan' => $user->jabatan,
            'akun_email' => $user->pegawai_email,
            'akun_lhkpn' => $user->pegawai_lhkpn,
            'akun_skp' => $user->pegawai_skp,
            'akun_pbb' => $user->pegawai_pbb

        ], 200);
    }
}
