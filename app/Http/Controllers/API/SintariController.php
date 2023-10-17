<?php

namespace App\Http\Controllers\API;

use App\Models\Libnas;
use App\Models\Pegawai;
use App\Models\Realisasi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Kalender;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class SintariController extends Controller
{
    public function index()
    {
        // hitungan kinerja harian
        $tahun = date('Y');
        $bulan = date('09');
        $tanggal_end = cal_days_in_month(CAL_GREGORIAN, $bulan, $tahun);
        $tgl_awal = date('Y-m-d', strtotime($tahun . "-" . $bulan . "-" . "1"));
        $tgl_akhir = date('Y-m-d', strtotime($tahun . "-" . $bulan . "-" . $tanggal_end));
        $awal = strtotime($tgl_awal);
        $akhir = strtotime($tgl_akhir);
        $user = Auth::user();
        $datauser = Pegawai::where('pegawai_id', $user->id_user)->first();
        $liburnas2 = Libnas::whereMonth('libur_mulai', date('09'))
            ->whereYear('libur_mulai', date('Y'))
            ->count();
        $bulanlalu = strtotime('-1 month');
        $sebulanlalu = date('06', $bulanlalu);
        $hariini = date('l, d F Y');

        //hari kerja full bulanan
        if ($user->pegawai_absensi == 2 or $user->pegawai_absensi == 3 or $user->pegawai_absensi == 4 or $user->pegawai_opd_gabung == '578') {
            $hari_kerja = 0;
            for ($i = $awal; $i <= $akhir; $i += (60 * 60 * 24)) {
                $i_date = date("Y-m-d", $i);
                if (date("w", $i) != "0") {
                    $hari_kerja++;
                }
            }
        }

        $hari_kerja = 0;
        for ($i = $awal; $i <= $akhir; $i += (60 * 60 * 24)) {
            $i_date = date("Y-m-d", $i);
            if (date("w", $i) != "0"  and date("w", $i) != "6") {
                $hari_kerja++;
            }
        }

        $get_hk = DB::table('sintari_kalender_561')
            ->select('kalender_tanggal', DB::raw('COUNT(*) as jumlah'))
            ->where('kalender_pegawai', $datauser->pegawai_id)
            ->whereYear('kalender_tanggalmulai', date('Y'))
            ->whereMonth('kalender_tanggalmulai', date('09'))
            ->groupBy('kalender_tanggal')
            ->get();

        $absensijumlah = (count($get_hk) - $liburnas2);

        $persenabsen = round(((count($get_hk) + $liburnas2) * 100) / $hari_kerja, 2);

        // pengurangan
        $pengurangantotal = DB::table('sintari_pengurangan_7')->where('pengurangan_pegawai', $datauser->pegawai_id)
            ->whereMonth('pengurangan_tanggal', '09')
            ->whereYear('pengurangan_tanggal', 'Y')
            ->sum('pengurangan_besar');

        $penguranganjumlah = DB::table('sintari_pengurangan_7')->where('pengurangan_pegawai', $datauser->pegawai_id)
            ->whereMonth('pengurangan_tanggal', '09')
            ->whereYear('pengurangan_tanggal', 'Y')
            ->count('pengurangan_besar');

        $absensipersentase = $persenabsen - $pengurangantotal;

        if($absensipersentase > 100 ){
            $persentaseabsensi = 100;
        }else{
            $persentaseabsensi = $absensipersentase;
        }

        $tpp = round((40 / 100) * $datauser->pegawai_tpp, 2);
        $tppasli = round(($tpp * $absensipersentase) / 100, 2);

        $menitkinerja = Realisasi::where('realisasi_user', $datauser->pegawai_id)
            ->whereMonth('realisasi_tanggal', date('09')) //bulan ny ganti suai kebutuhan 'm'
            ->whereYear('realisasi_tanggal', date('Y')) //tahun ny ganti suai kebutuhan
            ->sum('realisasi_waktu');

        $menitlibnas = Libnas::whereMonth('libur_mulai', date('09')) //bulan ny ganti suai kebutuhan 'm'
            ->whereYear('libur_mulai', date('Y'))
            ->sum('libur_menitpengurangan');

        //kinerja menit
        $jumlahmenitkinerja = $menitkinerja + $menitlibnas;

        //hasil akhir //kinerja persen
        $persentasekinerja = round(($jumlahmenitkinerja * 100) / 6750, 2);

        //tppTotal
        $tpptotal = ($tppasli + $persentasekinerja);

        $data = Pegawai::where('pegawai_id', $user->id_user)
            ->select('pegawai_id', 'pegawai_gambar', 'nama', 'nip', 'pegawai_pangkat', 'gol', 'jabatan', 'jabatan_baru', 'pegawai_email', 'pegawai_lhkpn', 'pegawai_skp', 'pegawai_pbb')
            ->first();

        return response()->json([
            'status' => true,
            'hari_ini' => $hariini,
            'tpp' => $tpptotal,
            'absensi_persentase' => $persentaseabsensi,
            'absensi_jumlah' => $absensijumlah,
            'kinerja_persen' => $persentasekinerja,
            'kinerja_menit' => $jumlahmenitkinerja,
            'pengurangan_persen' => $pengurangantotal,
            'pengurangan_jumlah' => $penguranganjumlah,
            'dd' => $sebulanlalu,
            'data_pegawai' => $data
        ], 200);
    }
}
