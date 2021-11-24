<?php
 
namespace App\Traits;
use App\Models\{UserInfo,RadUserGroup};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
 
trait CheckerTrait {
    private $block_priority = -999;
    private $unblock_priority = 0;
    private $groupname_blocked = 'daloRADIUS-Disabled-Users';
    public function usernameCID(Request $request){        
        try {
            switch ($request->method()) {
                case 'POST':
                    $query = DB::table('userinfo')
                    ->groupBy('radusergroup.username')
                    ->join('radusergroup', 'userinfo.username', '=', 'radusergroup.username')
                    ->where('userinfo.notes', $request['cid'])
                    ->select('radusergroup.username', 'userinfo.notes')
                    ->get(1);
                    break;
                case 'GET':
                    $query = DB::table('userinfo')
                    ->groupBy('userinfo.username')
                    ->join('radusergroup', 'userinfo.username', '=', 'radusergroup.username')
                    ->select('radusergroup.username', 'userinfo.notes')
                    ->get();
                    break;
                default:
                    return response()->json([
                        'success' => false,
                        'message' => 'false method',
                    ],202);
            }
            if(count($query) == 0) {
                return response()->json([
                    'success' => false,
                    'error' => 'empty_data',
                    'cid' => $request['cid'],
                    'message' => 'no data CID found',
                ],422);
            }
            return response()->json([
                'success' => true,
                'count' => count($query),
                'user' => $query,
            ],200);
        } catch (\Exception $e){
            return response()->json([
                'success' => false,
                'error' => 'check_error',
                'message' => 'API process error'
            ],422);
        }
    }
    public function blockStatus(Request $request) 
    {
        // ../api/rad-checkblock
        try {
            $query = DB::table('radusergroup')
            ->join('userinfo', 'radusergroup.username', '=', 'userinfo.username')
            ->where('radusergroup.username', $request['username'])
            ->where('radusergroup.groupname', $this->groupname_blocked)
            ->where('radusergroup.priority', $this->block_priority)
            ->first(); 
            return response()->json([
                'success' => true,
                'status' => true,
                'cid' => $query->notes,
                'user' => $request['username'],
                'message' => 'username '.$request['username'].' already blocked'
            ],202);

        } catch (\Exception $e){
            $query = DB::table('radusergroup')
            ->join('userinfo', 'radusergroup.username', '=', 'userinfo.username')
            ->where('radusergroup.username', $request['username'])
            ->first(); 
            return response()->json([
                'success' => true,
                'status' => false,
                'cid' => $query->notes,
                'user' => $request['username'],
                'message' => 'username '.$request['username'].' already unblocked'
            ],202);
        }
    }
}