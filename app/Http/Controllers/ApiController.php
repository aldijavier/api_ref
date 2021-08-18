<?php

namespace App\Http\Controllers;

use App\Models\{UserInfo, RadUserGroup};

use Illuminate\Http\Request;

class ApiController extends Controller
{
    const block_priority = -999;
    const unblock_priority = 0;
    const groupname_blocked = 'daloRADIUS-Disabled-Users';
    
    public function CheckRadusergroupUser(Request $request)
    {
        // ../api/rad-checkradusergroup
        $query = RadUserGroup::where('username', $request['username'])
        ->first();
        if(isset($query)) {
            return response()->json([
                'success' => true,
                'user' => $request['username'],
                'status' => true,
            ],200);
        } else {
            return response()->json([
                'success' => false,
                'status' => false,
                'message' => 'username '.$request['username'].' not found'
            ],202);
        }
    }
    public function CheckBlock(Request $request) 
    {
        // ../api/rad-checkblock
        try {
            $CheckRadusergroupUser = $this->CheckRadusergroupUser($request);
            if($CheckRadusergroupUser->getData()->status == false) {
                return $CheckRadusergroupUser;
            }
            $query =  RadUserGroup::where('username', $request['username'])
            ->where('groupname', ApiController::groupname_blocked)
            ->where('priority', ApiController::block_priority)
            ->firstOrFail(); 
            return response()->json([
                'success' => true,
                'status' => true,
                'user' => $request['username'],
                'message' => 'username '.$request['username'].' already blocked'
            ],200);

        } catch (\Exception $e){
            return response()->json([
                'success' => true,
                'status' => false,
                'user' => $request['username'],
                'message' => 'username '.$request['username'].' not blocked'
            ],202);
        }
    }

    public function UserInfo()
    {
        // ../api/rad-alluser
        $query =  UserInfo::get();
        return response()->json([
            'success' => true,
            'user' => $query,
        ],200);
    }

    public function FindByUsername(Request $request)
    {
        try {
            $query = UserInfo::where('username', $request['username'])->firstOrFail();
            return response()->json([
                'success' => true,
                'user' => $query,
            ],200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'user' => $request['username'],
                'message' => 'username not found'
            ],202);
        }
    }

    public function BlockUserConnection(Request $request)
    {
        // ../api/rad-block
        // return $this->FindByUsername($request['username']);
        // $find2 = json_decode($find->getContent());
        $find = $this->FindByUsername($request);
        if($find->getData()->success == true) {
            try {
                $CheckRadusergroupUser = $this->CheckRadusergroupUser($request);
                $CheckBlock = $this->CheckBlock($request);
                if($CheckBlock->getData()->success == false) {
                    return $CheckRadusergroupUser;
                }else if($CheckBlock->getData()->status ==  true) {
                    return $CheckBlock;                 
                } 
                $query2 = RadUserGroup::insert(['username' => $request['username'], 
                'groupname' => ApiController::groupname_blocked,
                'priority' =>  ApiController::block_priority]);                    
                return response()->json([
                    'success' => true,
                    'user' => $request['username'],
                    'message' => 'Block user successfully'
                ],200);          
            } catch (\Exception $e){
                return response()->json([
                    'success' => false,
                    'message' => 'Block user failed'
                ],202);
            }
        }
        else {
            return $find;
        }
    }
    public function UnblockUserConnection(Request $request)
    {
        // ../api/rad-unblock
        $find = $this->FindByUsername($request);
        if($find->getData()->success ==  true) {
            try {
                $CheckBlock = $this->CheckBlock($request);
                if($request['groupname'] != ApiController::groupname_blocked) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Not blocked user!'
                    ],202);   
                } else {
                    if($CheckBlock->getData()->status == false) {
                        return $CheckBlock;                  
                    } else {
                        $query2 =  RadUserGroup::where('username', $request['username'])
                        ->where('groupname', $request['groupname'])
                        ->where('groupname', ApiController::groupname_blocked)
                        ->where('priority', -999)
                        ->delete();                 
                        return response()->json([
                            'success' => true,
                            'user' => $request['username'],
                            'message' => 'Unblock user successfully'
                        ],200);  
                    }
                }
                         
            } catch (\Exception $e){
                return response()->json([
                    'success' => false,
                    'message' => 'Block user fail'
                ],202);
            }
        }
        else {
            return $find;
        }
    
    }    
}
