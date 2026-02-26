<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use Auth;
class HomeController extends Controller
{
    
    public function __construct()
    {
        $this->middleware('auth');
    }

    
    public function index(Request $request)
    {
        
        $id_user = Auth::user()->id;
        $year = date('Y');

        // REIMBURSEMENT
        $total = DB::select(DB::raw("SELECT sum(nominal_pengajuan) AS total FROM reimbursement WHERE id_user='$id_user'"))['0']->total;
        $settlement = DB::select(DB::raw("SELECT sum(nominal_pengajuan) AS total FROM reimbursement WHERE id_user='$id_user' AND status=5"))['0']->total;
        $reim = DB::select(DB::raw("SELECT * FROM reimbursement  LEFT JOIN users ON users.id = reimbursement.id_user WHERE id_user='$id_user' ORDER BY reimbursement.id DESC LIMIT 10"));
        
        $notif = DB::select(DB::raw("SELECT * FROM notif_push WHERE id_user='$id_user' ORDER BY id DESC LIMIT 10"));
        $jan =  DB::select(DB::raw("SELECT sum(nominal_pengajuan) AS total FROM reimbursement WHERE id_user='$id_user' AND month(date)=01 AND year(date)='$year'"))['0']->total;
        if ($jan==null) {
            $jan = 0;
        }        
        $jan_set =  DB::select(DB::raw("SELECT sum(nominal_pengajuan) AS total FROM reimbursement WHERE id_user='$id_user' AND month(date)=01 AND year(date)='$year' AND status=5"))['0']->total;
        if ($jan_set==null) {
            $jan_set = 0;
        } 

        $feb =  DB::select(DB::raw("SELECT sum(nominal_pengajuan) AS total FROM reimbursement WHERE id_user='$id_user' AND month(date)=02 AND year(date)='$year'"))['0']->total;
        if ($feb==null) {
            $feb = 0;
        }
        $feb_set =  DB::select(DB::raw("SELECT sum(nominal_pengajuan) AS total FROM reimbursement WHERE id_user='$id_user' AND month(date)=02 AND year(date)='$year' AND status=5"))['0']->total;
        if ($feb_set==null) {
            $feb_set = 0;
        }

        $mar =  DB::select(DB::raw("SELECT sum(nominal_pengajuan) AS total FROM reimbursement WHERE id_user='$id_user' AND month(date)=03 AND year(date)='$year'"))['0']->total;
        if ($mar==null) {
            $mar = 0;
        }
        $mar_set =  DB::select(DB::raw("SELECT sum(nominal_pengajuan) AS total FROM reimbursement WHERE id_user='$id_user' AND month(date)=03 AND year(date)='$year' AND status=5"))['0']->total;
        if ($mar_set==null) {
            $mar_set = 0;
        }

        $apr =  DB::select(DB::raw("SELECT sum(nominal_pengajuan) AS total FROM reimbursement WHERE id_user='$id_user' AND month(date)=04 AND year(date)='$year'"))['0']->total;
        if ($apr==null) {
            $apr = 0;
        }
        $apr_set =  DB::select(DB::raw("SELECT sum(nominal_pengajuan) AS total FROM reimbursement WHERE id_user='$id_user' AND month(date)=04 AND year(date)='$year' AND status=5"))['0']->total;
        if ($apr_set==null) {
            $apr_set = 0;
        }

        $mei =  DB::select(DB::raw("SELECT sum(nominal_pengajuan) AS total FROM reimbursement WHERE id_user='$id_user' AND month(date)=05 AND year(date)='$year'"))['0']->total;
        if ($mei==null) {
            $mei = 0;
        }
        $mei_set =  DB::select(DB::raw("SELECT sum(nominal_pengajuan) AS total FROM reimbursement WHERE id_user='$id_user' AND month(date)=05 AND year(date)='$year' AND status=5"))['0']->total;
        if ($mei_set==null) {
            $mei_set = 0;
        }

        $jun =  DB::select(DB::raw("SELECT sum(nominal_pengajuan) AS total FROM reimbursement WHERE id_user='$id_user' AND month(date)=06 AND year(date)='$year'"))['0']->total;
        if ($jun==null) {
            $jun = 0;
        }
        $jun_set =  DB::select(DB::raw("SELECT sum(nominal_pengajuan) AS total FROM reimbursement WHERE id_user='$id_user' AND month(date)=06 AND year(date)='$year' AND status=5"))['0']->total;
        if ($jun_set==null) {
            $jun_set = 0;
        }

        $jul =  DB::select(DB::raw("SELECT sum(nominal_pengajuan) AS total FROM reimbursement WHERE id_user='$id_user' AND month(date)=07 AND year(date)='$year'"))['0']->total;
        if ($jul==null) {
            $jul = 0;
        }
        $jul_set =  DB::select(DB::raw("SELECT sum(nominal_pengajuan) AS total FROM reimbursement WHERE id_user='$id_user' AND month(date)=07 AND year(date)='$year' AND status=5"))['0']->total;
        if ($jul_set==null) {
            $jul_set = 0;
        }

        $ags =  DB::select(DB::raw("SELECT sum(nominal_pengajuan) AS total FROM reimbursement WHERE id_user='$id_user' AND month(date)=08 AND year(date)='$year'"))['0']->total;
        if ($ags==null) {
            $ags = 0;
        }
        $ags_set =  DB::select(DB::raw("SELECT sum(nominal_pengajuan) AS total FROM reimbursement WHERE id_user='$id_user' AND month(date)=08 AND year(date)='$year' AND status=5"))['0']->total;
        if ($ags_set==null) {
            $ags_set = 0;
        }

        $sept =  DB::select(DB::raw("SELECT sum(nominal_pengajuan) AS total FROM reimbursement WHERE id_user='$id_user' AND month(date)=09 AND year(date)='$year'"))['0']->total;
        if ($sept==null) {
            $sept = 0;
        }
        $sept_set =  DB::select(DB::raw("SELECT sum(nominal_pengajuan) AS total FROM reimbursement WHERE id_user='$id_user' AND month(date)=09 AND year(date)='$year' AND status=5"))['0']->total;
        if ($sept_set==null) {
            $sept_set = 0;
        }

        $okt =  DB::select(DB::raw("SELECT sum(nominal_pengajuan) AS total FROM reimbursement WHERE id_user='$id_user' AND month(date)=10 AND year(date)='$year'"))['0']->total;
        if ($okt==null) {
            $okt = 0;
        }
        $okt_set =  DB::select(DB::raw("SELECT sum(nominal_pengajuan) AS total FROM reimbursement WHERE id_user='$id_user' AND month(date)=10 AND year(date)='$year' AND status=5"))['0']->total;
        if ($okt_set==null) {
            $okt_set = 0;
        }

        $nov = DB::select(DB::raw("SELECT sum(nominal_pengajuan) AS total FROM reimbursement WHERE id_user='$id_user' AND month(date)=11 AND year(date)='$year'"))['0']->total;
        if ($nov==null) {
            $nov = 0;
        }
        $nov_set = DB::select(DB::raw("SELECT sum(nominal_pengajuan) AS total FROM reimbursement WHERE id_user='$id_user' AND month(date)=11 AND year(date)='$year' AND status=5"))['0']->total;
        if ($nov_set==null) {
            $nov_set = 0;
        }

        $des = DB::select(DB::raw("SELECT sum(nominal_pengajuan) AS total FROM reimbursement WHERE id_user='$id_user' AND month(date)=12 AND year(date)='$year'"))['0']->total;
        if ($des==null) {
            $des = 0;
        }
        $des_set = DB::select(DB::raw("SELECT sum(nominal_pengajuan) AS total FROM reimbursement WHERE id_user='$id_user' AND month(date)=12 AND year(date)='$year' AND status=5"))['0']->total;
        if ($des_set==null) {
            $des_set = 0;
        }

        // CASH ADVANCE
        $total_cash = DB::select(DB::raw("SELECT sum(nominal_pengajuan) AS total FROM pengajuan WHERE id_user='$id_user'"))['0']->total;
        $settlement_cash = DB::select(DB::raw("SELECT sum(nominal_pengajuan) AS total FROM pencairan LEFT JOIN pengajuan ON pengajuan.id = pencairan.id_pengajuan WHERE id_user='$id_user' AND pencairan.status=1"))['0']->total;
        $cash = DB::select(DB::raw("SELECT * FROM pengajuan  LEFT JOIN users ON users.id = pengajuan.id_user LEFT JOIN master_project ON master_project.id = pengajuan.id_project WHERE id_user='$id_user' ORDER BY pengajuan.id DESC LIMIT 10"));
        $jan_cash =  DB::select(DB::raw("SELECT sum(nominal_pengajuan) AS total FROM pengajuan WHERE id_user='$id_user' AND month(created_at)=01 AND year(created_at)='$year'"))['0']->total;
        if ($jan_cash==null) {
            $jan_cash = 0;
        } 
        $jan_cash_set =  DB::select(DB::raw("SELECT sum(nominal_pengajuan) AS total FROM pencairan LEFT JOIN pengajuan ON pengajuan.id = pencairan.id_pengajuan WHERE id_user='$id_user' AND month(pengajuan.created_at)=01 AND year(pengajuan.created_at)='$year'"))['0']->total;
        if ($jan_cash_set==null) {
            $jan_cash_set = 0;
        } 

        $feb_cash =  DB::select(DB::raw("SELECT sum(nominal_pengajuan) AS total FROM pengajuan WHERE id_user='$id_user' AND month(created_at)=02 AND year(created_at)='$year'"))['0']->total;
        if ($feb_cash==null) {
            $feb_cash = 0;
        } 
        $feb_cash_set =  DB::select(DB::raw("SELECT sum(nominal_pengajuan) AS total FROM pencairan LEFT JOIN pengajuan ON pengajuan.id = pencairan.id_pengajuan WHERE id_user='$id_user' AND month(pengajuan.created_at)=02 AND year(pengajuan.created_at)='$year'"))['0']->total;
        if ($feb_cash_set==null) {
            $feb_cash_set = 0;
        } 

        $mar_cash =  DB::select(DB::raw("SELECT sum(nominal_pengajuan) AS total FROM pengajuan WHERE id_user='$id_user' AND month(created_at)=03 AND year(created_at)='$year'"))['0']->total;
        if ($mar_cash==null) {
            $mar_cash = 0;
        }
        $mar_cash_set =  DB::select(DB::raw("SELECT sum(nominal_pengajuan) AS total FROM pencairan LEFT JOIN pengajuan ON pengajuan.id = pencairan.id_pengajuan WHERE id_user='$id_user' AND month(pengajuan.created_at)=03 AND year(pengajuan.created_at)='$year'"))['0']->total;
        if ($mar_cash_set==null) {
            $mar_cash_set = 0;
        } 

        $apr_cash =  DB::select(DB::raw("SELECT sum(nominal_pengajuan) AS total FROM pengajuan WHERE id_user='$id_user' AND month(created_at)=04 AND year(created_at)='$year'"))['0']->total;
        if ($apr_cash==null) {
            $apr_cash = 0;
        }
        $apr_cash_set =  DB::select(DB::raw("SELECT sum(nominal_pengajuan) AS total FROM pencairan LEFT JOIN pengajuan ON pengajuan.id = pencairan.id_pengajuan WHERE id_user='$id_user' AND month(pengajuan.created_at)=04 AND year(pengajuan.created_at)='$year'"))['0']->total;
        if ($apr_cash_set==null) {
            $apr_cash_set = 0;
        } 

        $mei_cash =  DB::select(DB::raw("SELECT sum(nominal_pengajuan) AS total FROM pengajuan WHERE id_user='$id_user' AND month(created_at)=05 AND year(created_at)='$year'"))['0']->total;
        if ($mei_cash==null) {
            $mei_cash = 0;
        }
        $mei_cash_set =  DB::select(DB::raw("SELECT sum(nominal_pengajuan) AS total FROM pencairan LEFT JOIN pengajuan ON pengajuan.id = pencairan.id_pengajuan WHERE id_user='$id_user' AND month(pengajuan.created_at)=05 AND year(pengajuan.created_at)='$year'"))['0']->total;
        if ($mei_cash_set==null) {
            $mei_cash_set = 0;
        } 

        $jun_cash =  DB::select(DB::raw("SELECT sum(nominal_pengajuan) AS total FROM pengajuan WHERE id_user='$id_user' AND month(created_at)=06 AND year(created_at)='$year'"))['0']->total;
        if ($jun_cash==null) {
            $jun_cash = 0;
        }
        $jun_cash_set =  DB::select(DB::raw("SELECT sum(nominal_pengajuan) AS total FROM pencairan LEFT JOIN pengajuan ON pengajuan.id = pencairan.id_pengajuan WHERE id_user='$id_user' AND month(pengajuan.created_at)=06 AND year(pengajuan.created_at)='$year'"))['0']->total;
        if ($jun_cash_set==null) {
            $jun_cash_set = 0;
        } 

        $jul_cash =  DB::select(DB::raw("SELECT sum(nominal_pengajuan) AS total FROM pengajuan WHERE id_user='$id_user' AND month(created_at)=07 AND year(created_at)='$year'"))['0']->total;
        if ($jul_cash==null) {
            $jul_cash = 0;
        }
        $jul_cash_set =  DB::select(DB::raw("SELECT sum(nominal_pengajuan) AS total FROM pencairan LEFT JOIN pengajuan ON pengajuan.id = pencairan.id_pengajuan WHERE id_user='$id_user' AND month(pengajuan.created_at)=07 AND year(pengajuan.created_at)='$year'"))['0']->total;
        if ($jul_cash_set==null) {
            $jul_cash_set = 0;
        } 

        $ags_cash =  DB::select(DB::raw("SELECT sum(nominal_pengajuan) AS total FROM pengajuan WHERE id_user='$id_user' AND month(created_at)=08 AND year(created_at)='$year'"))['0']->total;
        if ($ags_cash==null) {
            $ags_cash = 0;
        } 
        $ags_cash_set =  DB::select(DB::raw("SELECT sum(nominal_pengajuan) AS total FROM pencairan LEFT JOIN pengajuan ON pengajuan.id = pencairan.id_pengajuan WHERE id_user='$id_user' AND month(pengajuan.created_at)=08 AND year(pengajuan.created_at)='$year'"))['0']->total;
        if ($ags_cash_set==null) {
            $ags_cash_set = 0;
        } 

        $sep_cash =  DB::select(DB::raw("SELECT sum(nominal_pengajuan) AS total FROM pengajuan WHERE id_user='$id_user' AND month(created_at)=09 AND year(created_at)='$year'"))['0']->total;
        if ($sep_cash==null) {
            $sep_cash = 0;
        }  
        $sep_cash_set =  DB::select(DB::raw("SELECT sum(nominal_pengajuan) AS total FROM pencairan LEFT JOIN pengajuan ON pengajuan.id = pencairan.id_pengajuan WHERE id_user='$id_user' AND month(pengajuan.created_at)=10 AND year(pengajuan.created_at)='$year'"))['0']->total;
        if ($sep_cash_set==null) {
            $sep_cash_set = 0;
        } 

        $okt_cash =  DB::select(DB::raw("SELECT sum(nominal_pengajuan) AS total FROM pengajuan WHERE id_user='$id_user' AND month(created_at)=10 AND year(created_at)='$year'"))['0']->total;
        if ($okt_cash==null) {
            $okt_cash = 0;
        }  
        $okt_cash_set =  DB::select(DB::raw("SELECT sum(nominal_pengajuan) AS total FROM pencairan LEFT JOIN pengajuan ON pengajuan.id = pencairan.id_pengajuan WHERE id_user='$id_user' AND month(pengajuan.created_at)=10 AND year(pengajuan.created_at)='$year'"))['0']->total;
        if ($okt_cash_set==null) {
            $okt_cash_set = 0;
        } 

        $nov_cash =  DB::select(DB::raw("SELECT sum(nominal_pengajuan) AS total FROM pengajuan WHERE id_user='$id_user' AND month(created_at)=11 AND year(created_at)='$year'"))['0']->total;
        if ($nov_cash==null) {
            $nov_cash = 0;
        }
        $nov_cash_set =  DB::select(DB::raw("SELECT sum(nominal_pengajuan) AS total FROM pencairan LEFT JOIN pengajuan ON pengajuan.id = pencairan.id_pengajuan WHERE id_user='$id_user' AND month(pengajuan.created_at)=11 AND year(pengajuan.created_at)='$year'"))['0']->total;
        if ($nov_cash_set==null) {
            $nov_cash_set = 0;
        } 

        $des_cash =  DB::select(DB::raw("SELECT sum(nominal_pengajuan) AS total FROM pengajuan WHERE id_user='$id_user' AND month(created_at)=12 AND year(created_at)='$year'"))['0']->total;
        if ($des_cash==null) {
            $des_cash = 0;
        }
        $des_cash_set =  DB::select(DB::raw("SELECT sum(nominal_pengajuan) AS total FROM pencairan LEFT JOIN pengajuan ON pengajuan.id = pencairan.id_pengajuan WHERE id_user='$id_user' AND month(pengajuan.created_at)=12 AND year(pengajuan.created_at)='$year'"))['0']->total;
        if ($des_cash_set==null) {
            $des_cash_set = 0;
        } 

        return view('home', ['reim' => $reim, 'cash' => $cash, 'total' => $total, 'notif' => $notif, 'jan' => $jan, 'feb' => $feb, 'mar' => $mar, 'apr' => $apr, 'mei' => $mei, 'jun' => $jun, 'jul' => $jul, 'ags' => $ags, 'sept' => $sept, 'okt' => $okt, 'nov' => $nov, 'des' => $des, 'settlement' => $settlement, 'jan_set' => $jan_set, 'feb_set' => $feb_set, 'mar_set' => $mar_set, 'apr_set' => $apr_set, 'mei_set' => $mei_set, 'jun_set' => $jun_set, 'jul_set' => $jul_set, 'ags_set' => $ags_set, 'sept_set' => $sept_set, 'okt_set' => $okt_set, 'nov_set' => $nov_set, 'des_set' => $des_set, 'total_cash' => $total_cash, 'settlement_cash' => $settlement_cash, 'jan_cash' => $jan_cash, 'feb_cash' => $feb_cash, 'mar_cash' => $mar_cash, 'apr_cash' => $apr_cash, 'mei_cash' => $mei_cash, 'apr_cash' => $apr_cash, 'mei_cash' => $mei_cash, 'jun_cash' => $jun_cash, 'jul_cash' => $jul_cash, 'ags_cash' => $ags_cash, 'sep_cash' => $sep_cash, 'okt_cash' => $okt_cash, 'nov_cash' => $nov_cash, 'des_cash' => $des_cash, 'jan_cash_set' => $jan_cash_set, 'feb_cash_set' => $feb_cash_set, 'mar_cash_set' => $mar_cash_set, 'apr_cash_set' => $apr_cash_set, 'mei_cash_set' => $mei_cash_set, 'jun_cash_set' => $jun_cash_set, 'jul_cash_set' => $jul_cash_set, 'ags_cash_set' => $ags_cash_set, 'sep_cash_set' => $sep_cash_set, 'okt_cash_set' => $okt_cash_set, 'nov_cash_set' => $nov_cash_set, 'des_cash_set' => $des_cash_set]);
    }


    public function showAll(Request $request)
    {
        
        $id_user = Auth::user()->id;        
        $year = date('Y');

        // REIMBURSEMENT
        $total = DB::select(DB::raw("SELECT sum(nominal_pengajuan) AS total FROM reimbursement"))['0']->total;
        $settlement = DB::select(DB::raw("SELECT sum(nominal_pengajuan) AS total FROM reimbursement WHERE status=5"))['0']->total;
        $reim = DB::select(DB::raw("SELECT * FROM reimbursement  LEFT JOIN users ON users.id = reimbursement.id_user  ORDER BY reimbursement.id DESC LIMIT 10"));
        $cash = DB::select(DB::raw("SELECT * FROM pengajuan  LEFT JOIN users ON users.id = pengajuan.id_user LEFT JOIN master_project ON master_project.id = pengajuan.id_project ORDER BY pengajuan.id DESC LIMIT 10"));
        $notif = DB::select(DB::raw("SELECT * FROM notif_push  ORDER BY id DESC LIMIT 10"));
        $jan =  DB::select(DB::raw("SELECT sum(nominal_pengajuan) AS total FROM reimbursement WHERE month(date)=01 AND year(date)='$year'"))['0']->total;
        if ($jan==null) {
            $jan = 0;
        }        
        $jan_set =  DB::select(DB::raw("SELECT sum(nominal_pengajuan) AS total FROM reimbursement WHERE month(date)=01 AND year(date)='$year' AND status=5"))['0']->total;
        if ($jan_set==null) {
            $jan_set = 0;
        } 

        $feb =  DB::select(DB::raw("SELECT sum(nominal_pengajuan) AS total FROM reimbursement WHERE month(date)=02 AND year(date)='$year'"))['0']->total;
        if ($feb==null) {
            $feb = 0;
        }
        $feb_set =  DB::select(DB::raw("SELECT sum(nominal_pengajuan) AS total FROM reimbursement WHERE month(date)=02 AND year(date)='$year' AND status=5"))['0']->total;
        if ($feb_set==null) {
            $feb_set = 0;
        }

        $mar =  DB::select(DB::raw("SELECT sum(nominal_pengajuan) AS total FROM reimbursement WHERE month(date)=03 AND year(date)='$year'"))['0']->total;
        if ($mar==null) {
            $mar = 0;
        }
        $mar_set =  DB::select(DB::raw("SELECT sum(nominal_pengajuan) AS total FROM reimbursement WHERE month(date)=03 AND year(date)='$year' AND status=5"))['0']->total;
        if ($mar_set==null) {
            $mar_set = 0;
        }

        $apr =  DB::select(DB::raw("SELECT sum(nominal_pengajuan) AS total FROM reimbursement WHERE month(date)=04 AND year(date)='$year'"))['0']->total;
        if ($apr==null) {
            $apr = 0;
        }
        $apr_set =  DB::select(DB::raw("SELECT sum(nominal_pengajuan) AS total FROM reimbursement WHERE month(date)=04 AND year(date)='$year' AND status=5"))['0']->total;
        if ($apr_set==null) {
            $apr_set = 0;
        }

        $mei =  DB::select(DB::raw("SELECT sum(nominal_pengajuan) AS total FROM reimbursement WHERE month(date)=05 AND year(date)='$year'"))['0']->total;
        if ($mei==null) {
            $mei = 0;
        }
        $mei_set =  DB::select(DB::raw("SELECT sum(nominal_pengajuan) AS total FROM reimbursement WHERE month(date)=05 AND year(date)='$year' AND status=5"))['0']->total;
        if ($mei_set==null) {
            $mei_set = 0;
        }

        $jun =  DB::select(DB::raw("SELECT sum(nominal_pengajuan) AS total FROM reimbursement WHERE month(date)=06 AND year(date)='$year'"))['0']->total;
        if ($jun==null) {
            $jun = 0;
        }
        $jun_set =  DB::select(DB::raw("SELECT sum(nominal_pengajuan) AS total FROM reimbursement WHERE month(date)=06 AND year(date)='$year' AND status=5"))['0']->total;
        if ($jun_set==null) {
            $jun_set = 0;
        }

        $jul =  DB::select(DB::raw("SELECT sum(nominal_pengajuan) AS total FROM reimbursement WHERE month(date)=07 AND year(date)='$year'"))['0']->total;
        if ($jul==null) {
            $jul = 0;
        }
        $jul_set =  DB::select(DB::raw("SELECT sum(nominal_pengajuan) AS total FROM reimbursement WHERE month(date)=07 AND year(date)='$year' AND status=5"))['0']->total;
        if ($jul_set==null) {
            $jul_set = 0;
        }

        $ags =  DB::select(DB::raw("SELECT sum(nominal_pengajuan) AS total FROM reimbursement WHERE month(date)=08 AND year(date)='$year'"))['0']->total;
        if ($ags==null) {
            $ags = 0;
        }
        $ags_set =  DB::select(DB::raw("SELECT sum(nominal_pengajuan) AS total FROM reimbursement WHERE month(date)=08 AND year(date)='$year' AND status=5"))['0']->total;
        if ($ags_set==null) {
            $ags_set = 0;
        }

        $sept =  DB::select(DB::raw("SELECT sum(nominal_pengajuan) AS total FROM reimbursement WHERE month(date)=09 AND year(date)='$year'"))['0']->total;
        if ($sept==null) {
            $sept = 0;
        }
        $sept_set =  DB::select(DB::raw("SELECT sum(nominal_pengajuan) AS total FROM reimbursement WHERE month(date)=09 AND year(date)='$year' AND status=5"))['0']->total;
        if ($sept_set==null) {
            $sept_set = 0;
        }

        $okt =  DB::select(DB::raw("SELECT sum(nominal_pengajuan) AS total FROM reimbursement WHERE month(date)=10 AND year(date)='$year'"))['0']->total;
        if ($okt==null) {
            $okt = 0;
        }
        $okt_set =  DB::select(DB::raw("SELECT sum(nominal_pengajuan) AS total FROM reimbursement WHERE month(date)=10 AND year(date)='$year' AND status=5"))['0']->total;
        if ($okt_set==null) {
            $okt_set = 0;
        }

        $nov = DB::select(DB::raw("SELECT sum(nominal_pengajuan) AS total FROM reimbursement WHERE month(date)=11 AND year(date)='$year'"))['0']->total;
        if ($nov==null) {
            $nov = 0;
        }
        $nov_set = DB::select(DB::raw("SELECT sum(nominal_pengajuan) AS total FROM reimbursement WHERE month(date)=11 AND year(date)='$year' AND status=5"))['0']->total;
        if ($nov_set==null) {
            $nov_set = 0;
        }

        $des = DB::select(DB::raw("SELECT sum(nominal_pengajuan) AS total FROM reimbursement WHERE month(date)=12 AND year(date)='$year'"))['0']->total;
        if ($des==null) {
            $des = 0;
        }
        $des_set = DB::select(DB::raw("SELECT sum(nominal_pengajuan) AS total FROM reimbursement WHERE month(date)=12 AND year(date)='$year' AND status=5"))['0']->total;
        if ($des_set==null) {
            $des_set = 0;
        }

        // CASH ADVANCE

        $total_cash = DB::select(DB::raw("SELECT sum(nominal_pengajuan) AS total FROM pengajuan"))['0']->total;
        $settlement_cash = DB::select(DB::raw("SELECT sum(nominal_pengajuan) AS total FROM pencairan LEFT JOIN pengajuan ON pengajuan.id = pencairan.id_pengajuan WHERE pencairan.status=1"))['0']->total;
        $cash = DB::select(DB::raw("SELECT * FROM pengajuan  LEFT JOIN users ON users.id = pengajuan.id_user LEFT JOIN master_project ON master_project.id = pengajuan.id_project ORDER BY pengajuan.id DESC LIMIT 10"));
        $jan_cash =  DB::select(DB::raw("SELECT sum(nominal_pengajuan) AS total FROM pengajuan WHERE month(created_at)=01 AND year(created_at)='$year'"))['0']->total;
        if ($jan_cash==null) {
            $jan_cash = 0;
        } 
        $jan_cash_set =  DB::select(DB::raw("SELECT sum(nominal_pengajuan) AS total FROM pencairan LEFT JOIN pengajuan ON pengajuan.id = pencairan.id_pengajuan WHERE month(pengajuan.created_at)=01 AND year(pengajuan.created_at)='$year'"))['0']->total;
        if ($jan_cash_set==null) {
            $jan_cash_set = 0;
        } 

        $feb_cash =  DB::select(DB::raw("SELECT sum(nominal_pengajuan) AS total FROM pengajuan WHERE month(created_at)=02 AND year(created_at)='$year'"))['0']->total;
        if ($feb_cash==null) {
            $feb_cash = 0;
        } 
        $feb_cash_set =  DB::select(DB::raw("SELECT sum(nominal_pengajuan) AS total FROM pencairan LEFT JOIN pengajuan ON pengajuan.id = pencairan.id_pengajuan WHERE month(pengajuan.created_at)=02 AND year(pengajuan.created_at)='$year'"))['0']->total;
        if ($feb_cash_set==null) {
            $feb_cash_set = 0;
        } 

        $mar_cash =  DB::select(DB::raw("SELECT sum(nominal_pengajuan) AS total FROM pengajuan WHERE month(created_at)=03 AND year(created_at)='$year'"))['0']->total;
        if ($mar_cash==null) {
            $mar_cash = 0;
        }
        $mar_cash_set =  DB::select(DB::raw("SELECT sum(nominal_pengajuan) AS total FROM pencairan LEFT JOIN pengajuan ON pengajuan.id = pencairan.id_pengajuan WHERE month(pengajuan.created_at)=03 AND year(pengajuan.created_at)='$year'"))['0']->total;
        if ($mar_cash_set==null) {
            $mar_cash_set = 0;
        } 

        $apr_cash =  DB::select(DB::raw("SELECT sum(nominal_pengajuan) AS total FROM pengajuan WHERE month(created_at)=04 AND year(created_at)='$year'"))['0']->total;
        if ($apr_cash==null) {
            $apr_cash = 0;
        }
        $apr_cash_set =  DB::select(DB::raw("SELECT sum(nominal_pengajuan) AS total FROM pencairan LEFT JOIN pengajuan ON pengajuan.id = pencairan.id_pengajuan WHERE month(pengajuan.created_at)=04 AND year(pengajuan.created_at)='$year'"))['0']->total;
        if ($apr_cash_set==null) {
            $apr_cash_set = 0;
        } 

        $mei_cash =  DB::select(DB::raw("SELECT sum(nominal_pengajuan) AS total FROM pengajuan WHERE month(created_at)=05 AND year(created_at)='$year'"))['0']->total;
        if ($mei_cash==null) {
            $mei_cash = 0;
        }
        $mei_cash_set =  DB::select(DB::raw("SELECT sum(nominal_pengajuan) AS total FROM pencairan LEFT JOIN pengajuan ON pengajuan.id = pencairan.id_pengajuan WHERE month(pengajuan.created_at)=05 AND year(pengajuan.created_at)='$year'"))['0']->total;
        if ($mei_cash_set==null) {
            $mei_cash_set = 0;
        } 

        $jun_cash =  DB::select(DB::raw("SELECT sum(nominal_pengajuan) AS total FROM pengajuan WHERE month(created_at)=06 AND year(created_at)='$year'"))['0']->total;
        if ($jun_cash==null) {
            $jun_cash = 0;
        }
        $jun_cash_set =  DB::select(DB::raw("SELECT sum(nominal_pengajuan) AS total FROM pencairan LEFT JOIN pengajuan ON pengajuan.id = pencairan.id_pengajuan WHERE month(pengajuan.created_at)=06 AND year(pengajuan.created_at)='$year'"))['0']->total;
        if ($jun_cash_set==null) {
            $jun_cash_set = 0;
        } 

        $jul_cash =  DB::select(DB::raw("SELECT sum(nominal_pengajuan) AS total FROM pengajuan WHERE month(created_at)=07 AND year(created_at)='$year'"))['0']->total;
        if ($jul_cash==null) {
            $jul_cash = 0;
        }
        $jul_cash_set =  DB::select(DB::raw("SELECT sum(nominal_pengajuan) AS total FROM pencairan LEFT JOIN pengajuan ON pengajuan.id = pencairan.id_pengajuan WHERE month(pengajuan.created_at)=07 AND year(pengajuan.created_at)='$year'"))['0']->total;
        if ($jul_cash_set==null) {
            $jul_cash_set = 0;
        } 

        $ags_cash =  DB::select(DB::raw("SELECT sum(nominal_pengajuan) AS total FROM pengajuan WHERE month(created_at)=08 AND year(created_at)='$year'"))['0']->total;
        if ($ags_cash==null) {
            $ags_cash = 0;
        } 
        $ags_cash_set =  DB::select(DB::raw("SELECT sum(nominal_pengajuan) AS total FROM pencairan LEFT JOIN pengajuan ON pengajuan.id = pencairan.id_pengajuan WHERE month(pengajuan.created_at)=08 AND year(pengajuan.created_at)='$year'"))['0']->total;
        if ($ags_cash_set==null) {
            $ags_cash_set = 0;
        } 

        $sep_cash =  DB::select(DB::raw("SELECT sum(nominal_pengajuan) AS total FROM pengajuan WHERE month(created_at)=09 AND year(created_at)='$year'"))['0']->total;
        if ($sep_cash==null) {
            $sep_cash = 0;
        }  
        $sep_cash_set =  DB::select(DB::raw("SELECT sum(nominal_pengajuan) AS total FROM pencairan LEFT JOIN pengajuan ON pengajuan.id = pencairan.id_pengajuan WHERE month(pengajuan.created_at)=10 AND year(pengajuan.created_at)='$year'"))['0']->total;
        if ($sep_cash_set==null) {
            $sep_cash_set = 0;
        } 

        $okt_cash =  DB::select(DB::raw("SELECT sum(nominal_pengajuan) AS total FROM pengajuan WHERE month(created_at)=10 AND year(created_at)='$year'"))['0']->total;
        if ($okt_cash==null) {
            $okt_cash = 0;
        }  
        $okt_cash_set =  DB::select(DB::raw("SELECT sum(nominal_pengajuan) AS total FROM pencairan LEFT JOIN pengajuan ON pengajuan.id = pencairan.id_pengajuan WHERE month(pengajuan.created_at)=10 AND year(pengajuan.created_at)='$year'"))['0']->total;
        if ($okt_cash_set==null) {
            $okt_cash_set = 0;
        } 

        $nov_cash =  DB::select(DB::raw("SELECT sum(nominal_pengajuan) AS total FROM pengajuan WHERE month(created_at)=11 AND year(created_at)='$year'"))['0']->total;
        if ($nov_cash==null) {
            $nov_cash = 0;
        }
        $nov_cash_set =  DB::select(DB::raw("SELECT sum(nominal_pengajuan) AS total FROM pencairan LEFT JOIN pengajuan ON pengajuan.id = pencairan.id_pengajuan WHERE month(pengajuan.created_at)=11 AND year(pengajuan.created_at)='$year'"))['0']->total;
        if ($nov_cash_set==null) {
            $nov_cash_set = 0;
        } 

        $des_cash =  DB::select(DB::raw("SELECT sum(nominal_pengajuan) AS total FROM pengajuan WHERE month(created_at)=12 AND year(created_at)='$year'"))['0']->total;
        if ($des_cash==null) {
            $des_cash = 0;
        }
        $des_cash_set =  DB::select(DB::raw("SELECT sum(nominal_pengajuan) AS total FROM pencairan LEFT JOIN pengajuan ON pengajuan.id = pencairan.id_pengajuan WHERE month(pengajuan.created_at)=12 AND year(pengajuan.created_at)='$year'"))['0']->total;
        if ($des_cash_set==null) {
            $des_cash_set = 0;
        } 

        return view('home-all', ['reim' => $reim, 'cash' => $cash, 'total' => $total, 'notif' => $notif, 'jan' => $jan, 'feb' => $feb, 'mar' => $mar, 'apr' => $apr, 'mei' => $mei, 'jun' => $jun, 'jul' => $jul, 'ags' => $ags, 'sept' => $sept, 'okt' => $okt, 'nov' => $nov, 'des' => $des, 'settlement' => $settlement, 'jan_set' => $jan_set, 'feb_set' => $feb_set, 'mar_set' => $mar_set, 'apr_set' => $apr_set, 'mei_set' => $mei_set, 'jun_set' => $jun_set, 'jul_set' => $jul_set, 'ags_set' => $ags_set, 'sept_set' => $sept_set, 'okt_set' => $okt_set, 'nov_set' => $nov_set, 'des_set' => $des_set, 'total_cash' => $total_cash, 'settlement_cash' => $settlement_cash, 'jan_cash' => $jan_cash, 'feb_cash' => $feb_cash, 'mar_cash' => $mar_cash, 'apr_cash' => $apr_cash, 'mei_cash' => $mei_cash, 'apr_cash' => $apr_cash, 'mei_cash' => $mei_cash, 'jun_cash' => $jun_cash, 'jul_cash' => $jul_cash, 'ags_cash' => $ags_cash, 'sep_cash' => $sep_cash, 'okt_cash' => $okt_cash, 'nov_cash' => $nov_cash, 'des_cash' => $des_cash, 'jan_cash_set' => $jan_cash_set, 'feb_cash_set' => $feb_cash_set, 'mar_cash_set' => $mar_cash_set, 'apr_cash_set' => $apr_cash_set, 'mei_cash_set' => $mei_cash_set, 'jun_cash_set' => $jun_cash_set, 'jul_cash_set' => $jul_cash_set, 'ags_cash_set' => $ags_cash_set, 'sep_cash_set' => $sep_cash_set, 'okt_cash_set' => $okt_cash_set, 'nov_cash_set' => $nov_cash_set, 'des_cash_set' => $des_cash_set]);
    }

    public function filtergrap($id)
    {
        $id_user = Auth::user()->id;
        $jabatan = Auth::user()->jabatan;
        if ($jabatan == 'karyawan') {
            $data = DB::table('pengajuan')
                ->select('nominal_pengajuan')
                ->where('status', 3)
                ->where('pengajuan.id_user', $id_user);
            if ($id != 0) {
                $first = $id;
                $last = $id;
                $tahun = date("Y");
                $from = $tahun . '-' . $first . '-01';
                $to = $tahun . '-' . $last . '-30';

                $data = $data->whereBetween('pengajuan.created_at', [$from, $to]);
            } else {
                $first = date("m");
                $last = date("m");
                $tahun = date("Y");
                $from = $tahun . '-' . $first . '-01';
                $to = $tahun . '-' . $last . '-30';

                $data = $data->whereBetween('pengajuan.created_at', [$from, $to]);
            }
            $data = $data->get();

            $array = [];
            foreach ($data as $key) {
                $array[] = number_format($key->nominal_pengajuan);
            }
        } else {
            $data = DB::table('pengajuan')
                ->select('nominal_pengajuan')
                ->where('status', 3);
            if ($id != 0) {
                $first = $id;
                $last = $id;
                $tahun = date("Y");
                $from = $tahun . '-' . $first . '-01';
                $to = $tahun . '-' . $last . '-30';

                $data = $data->whereBetween('pengajuan.created_at', [$from, $to]);
            } else {
                $first = date("m");
                $last = date("m");
                $tahun = date("Y");
                $from = $tahun . '-' . $first . '-01';
                $to = $tahun . '-' . $last . '-30';

                $data = $data->whereBetween('pengajuan.created_at', [$from, $to]);
            }
            $data = $data->get();

            $array = [];
            foreach ($data as $key) {
                $array[] = number_format($key->nominal_pengajuan);
            }
        }
        return response()->json($data);
    }

    public function totalfilter($id)
    {
        $id_user = Auth::user()->id;
        $jabatan = Auth::user()->jabatan;
        if ($jabatan == 'karyawan') {
            $data = DB::table('pengajuan')
                ->select('nominal_pengajuan')
                ->where('status', 3)
                ->where('pengajuan.id_user', $id_user);

            if ($id != 0) {
                $first = $id;
                $last = $id;
                $tahun = date("Y");
                $from = $tahun . '-' . $first . '-01';
                $to = $tahun . '-' . $last . '-30';

                $data = $data->whereBetween('pengajuan.created_at', [$from, $to]);
            } else {
                $first = date("m");
                $last = date("m");
                $tahun = date("Y");
                $from = $tahun . '-' . $first . '-01';
                $to = $tahun . '-' . $last . '-30';

                $data = $data->whereBetween('pengajuan.created_at', [$from, $to]);
            }
            $data = $data->sum('nominal_pengajuan');

            $array = [];
            $array[] = number_format($data, 0, ',', '.');
        } else {
            $data = DB::table('pengajuan')
                ->select('nominal_pengajuan')
                ->where('status', 3);

            if ($id != 0) {
                $first = $id;
                $last = $id;
                $tahun = date("Y");
                $from = $tahun . '-' . $first . '-01';
                $to = $tahun . '-' . $last . '-30';

                $data = $data->whereBetween('pengajuan.created_at', [$from, $to]);
            } else {
                $first = date("m");
                $last = date("m");
                $tahun = date("Y");
                $from = $tahun . '-' . $first . '-01';
                $to = $tahun . '-' . $last . '-30';

                $data = $data->whereBetween('pengajuan.created_at', [$from, $to]);
            }
            $data = $data->sum('nominal_pengajuan');

            $array = [];
            $array[] = number_format($data, 0, ',', '.');
        }
        return response()->json($array);
    }
}
