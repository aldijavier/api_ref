<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

use Lubus\Constants\Status;
// use Sofa\Eloquence\Eloquence;

class Referral_Ext extends Model
{
    protected $table = 'referral_ext';

    protected $fillable = ['referral_code', 'referral_for', 'start_date', 'end_date', 'province', 'regencies',
    'description', 'status'];

    // use Eloquence;
    // use createdByUser, updatedByUser;

    protected $searchableColumns = [
        'referral_code' => 200
        // 'plan_name' => 10,
        // 'plan_details' => 5,
    ];

    //Scope Queries
    public function scopeIndexQuery($query, $sorting_field, $sorting_direction, $drp_start, $drp_end)
    {
        $sorting_field = ($sorting_field != null ? $sorting_field : 'start_date');
        $sorting_direction = ($sorting_direction != null ? $sorting_direction : 'asc');

        if ($drp_start == null or $drp_end == null) {
            return $query->select('referral.id', 'referral.referral_code', 'referral.referral_for', 'referral.start_date', 'referral.end_date', 'referral.description', 'referral.status')->orderBy($sorting_field, $sorting_direction);
        }

        return $query->select('referral.id', 'referral.referral_code', 'referral.referral_for', 'referral.start_date', 'referral.end_date', 'referral.description', 'referral.status')->whereBetween('referral.start_date', [
            $drp_start,
            $drp_end,
        ])->orderBy($sorting_field, $sorting_direction);
    }

    public function scopeRecent($query)
    {
        return $query->where('start_date', '<=', Carbon::today())->take(10)->orderBy('start_date', 'desc');
    }


    // Laravel issue: Workaroud Needed
    public function scopeRegistrations($query, $month, $year)
    {
        return $query->whereMonth('start_date', '=', $month)->whereYear('start_date', '=', $year)->count();
    }

    public function getPlanDisplayAttribute()
    {
        return $this->referral_code.' @ '.$this->amount.' For '.$this->days.' Days';
    }

    public function scopeExcludeArchive($query)
    {
        return $query->where('referral_code', '!=', \constStatus::Archive);
    }

    public function scopeOnlyActive($query)
    {
        return $query->where('referral_code', '=', \constStatus::Active);
    }

    public function subscriptions()
    {
        return $this->hasMany('App\Subscription', 'service_id');
    }

    public function service()
    {
        return $this->belongsTo('App\Service', 'service_id');
    }

    public function scopeFilterDates($query)
    {
        $date = explode(" - ", request()->input('from-to', "")); 

        if(count($date) != 2)
        {
            $date = [now()->subDays(29)->format("Y-m-d"), now()->format("Y-m-d")];
        }

        return $query->whereBetween('start_date', $date);
    }
}
