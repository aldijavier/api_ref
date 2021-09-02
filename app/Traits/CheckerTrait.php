<?php
 
namespace App\Traits;
use App\Models\{UserInfo,RadUserGroup};
use Illuminate\Http\Request;
use Validator;
 
trait CheckerTrait {
    private $block_priority = -999;
    private $unblock_priority = 0;
    private $groupname_blocked = 'daloRADIUS-Disabled-Users';
    public function usernameCID(Request $request){        
        try {
            switch ($request->method()) {
                case 'POST':
                    $query = UserInfo::
                    groupBy('radusergroup.username')
                    ->join('radusergroup', 'userinfo.username', '=', 'radusergroup.username')
                    ->where('userinfo.notes', $request['cid'])
                    ->select('radusergroup.username', 'userinfo.notes')
                    ->get(1);
                    break;
                case 'GET':
                    $query = UserInfo::
                    groupBy('userinfo.username')
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
            $query =  RadUserGroup::where('username', $request['username'])
            ->where('groupname', $this->groupname_blocked)
            ->where('priority', $this->block_priority)
            ->firstOrFail(); 
            return response()->json([
                'success' => true,
                'status' => true,
                'user' => $request['username'],
                'message' => 'username '.$request['username'].' already blocked'
            ],202);

        } catch (\Exception $e){
            return response()->json([
                'success' => true,
                'status' => false,
                'user' => $request['username'],
                'message' => 'username '.$request['username'].' already unblocked'
            ],202);
        }
    }
}