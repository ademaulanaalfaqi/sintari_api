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
        $bulan = date('m');

        $tanggal_end = cal_days_in_month(CAL_GREGORIAN, $bulan, $tahun);;

        $tgl_awal = date('Y-m-d', strtotime($tahun . "-" . $bulan . "-" . "1"));
        $tgl_akhir = date('Y-m-d', strtotime($tahun . "-" . $bulan . "-" . $tanggal_end));
        $awal = strtotime($tgl_awal);
        $akhir = strtotime($tgl_akhir);

        //libur nasional
        $liburnas = Libnas::whereMonth('libur_mulai', date('07')) //bulan ny ganti suai kebutuhan 'm'
            ->whereYear('libur_mulai', date('Y'))->count(); //tahun ny ganti suai kebutuhan

        if ($user->pegawai_absensi == 2 or $user->pegawai_absensi == 3 or $user->pegawai_absensi == 4   or $user->pegawai_opd_gabung == '578') {
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

        //jumlah absen
        $hk = DB::table('sintari_kalender_561')
        ->select('kalender_tanggal', DB::raw('COUNT(*) as jumlah'))
        ->where('kalender_pegawai', $user->pegawai_id)
        ->whereMonth('kalender_tanggalmulai', date('07')) //bulan ny ganti suai kebutuhan 'm'
        ->whereYear('kalender_tanggalmulai', date('Y')) //tahun ny ganti suai kebutuhan
        ->groupBy('kalender_tanggal')
        ->orderBy('kalender_tanggal','ASC')
        ->get();

        $a = round(((count($hk)+$liburnas)*100)/$hari_kerja,2);

        //pengurangan 
        $pengurangan = Pengurangan::where('pengurangan_pegawai',$user->pegawai_id)
        ->whereMonth('pengurangan_tanggal',date('07')) //bulan ny ganti suai kebutuhan 'm'
        ->whereYear('pengurangan_tanggal',date('Y')) //tahun ny ganti suai kebutuhan
        ->sum('pengurangan_besar');

        //absen persen
        $persentaseabsen = $a - $pengurangan;

        $tunjangan = round((40/100)*$user->pegawai_tpp,2);

        //hasil akhir
        $tunjanganasli = round(($tunjangan*$persentaseabsen)/100,2);

        $jumenitkinerja = Realisasi::where('realisasi_user',$user->pegawai_id)
        ->whereMonth('realisasi_tanggal',date('07')) //bulan ny ganti suai kebutuhan 'm'
        ->whereYear('realisasi_tanggal',date('Y')) //tahun ny ganti suai kebutuhan
        ->sum('realisasi_waktu');

        $menitlibnas = Libnas::whereMonth('libur_mulai',date('07')) //bulan ny ganti suai kebutuhan 'm'
        ->whereYear('libur_mulai',date('Y'))
        ->sum('libur_menitpengurangan');

        //kinerja menit
        $jumenitkinerjasli = $jumenitkinerja + $menitlibnas;

        //hasil akhir //kinerja persen
        $persentasekinerja = round(($jumenitkinerjasli*100)/6750,2);

        //tppTotal
        $tppTotal = ($tunjanganasli + $persentasekinerja);

        return response()->json([
            'status' => true,
            'message' => 'Data Ditemukan',
            'data' => $tppTotal
        ], 200);

    }
}
