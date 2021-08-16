<?php

namespace App\Http\Controllers;

use App\Models\{UserInfo, RadUserGroup};

use Illuminate\Http\Request;

class ApiController extends Controller
{
    public function UserInfo()
    {
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
                'message' => 'username not found'
            ],202);
        }
    }

    public function BlockUserConnection(Request $request)
    {
        // ../api/rad-block
        // return $this->FindByUsername($request['username']);
        $find = $this->FindByUsername($request);
        // $find2 = json_decode($find->getContent());
        if($find->getData()->success ==  true) {
            try {
                $query =  RadUserGroup::where('username', $request['username'])
                ->where('groupname', $request['groupname'])
                ->where('groupname', '!=', 'daloRADIUS-Disabled-Users')
                ->where('priority', 0)
                ->first();
                $query1 =  RadUserGroup::where('username', $request['username'])
                ->where('groupname','daloRADIUS-Disabled-Users')
                ->where('priority', -999)
                ->first();          
                if(isset($query1)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'User '.$request['username'].' already blocked!'
                    ],202);                      
                } else {
                    $query2 = RadUserGroup::insert(['username' => $query['username'], 
                    'groupname' => 'daloRADIUS-Disabled-Users',
                    'priority' =>  -999]);                    
                    return response()->json([
                        'success' => true,
                        'user' => $query,
                    ],200);  
                }
                         
            } catch (\Exception $e){
                return response()->json([
                    'success' => false,
                    'message' => 'Block user fail'
                ],202);
            }
        }
        else {
            return response()->json([
                'success' => false,
                'message' => 'username not found'
            ],202);
        }
    }
    public function UnblockUserConnection(Request $request)
    {
        // ../api/rad-unblock
        $find = $this->FindByUsername($request);
        if($find->getData()->success ==  true) {
            try {
                if($request['groupname'] != 'daloRADIUS-Disabled-Users') {
                    return response()->json([
                        'success' => false,
                        'message' => 'Not blocked user!'
                    ],202);   
                } else {
                    $query =  RadUserGroup::where('username', $request['username'])
                    ->where('groupname','daloRADIUS-Disabled-Users')
                    ->where('groupname', $request['groupname'])
                    ->where('priority', $request['priority'])
                    ->first();
                    $query1 =  RadUserGroup::where('username', $request['username'])
                    ->where('groupname', '!=','daloRADIUS-Disabled-Users')
                    ->first();          
                    if(isset($query1) and !isset($query)) {
                        return response()->json([
                            'success' => false,
                            'user' => $query1,
                            'message' => 'The user has not been blocked!'
                        ],202);                      
                    } else {
                        $query2 =  RadUserGroup::where('username', $request['username'])
                        ->where('groupname', $request['groupname'])
                        ->where('groupname', 'daloRADIUS-Disabled-Users')
                        ->where('priority', $request['priority'])
                        ->delete();                 
                        return response()->json([
                            'success' => true,
                            'user' => $query,
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
            return response()->json([
                'success' => false,
                'message' => 'username not found'
            ],202);
        }
    
    }    
}
