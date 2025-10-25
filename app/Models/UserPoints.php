<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserPoints extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'points_earned',
        'points_spent',
        'current_points',
        'level',
        'total_earned',
        'monthly_goal',
        'monthly_progress'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function history()
    {
        return $this->hasMany(PointsHistory::class, 'user_id', 'user_id');
    }

    public function calculateLevel()
    {
        $totalEarned = $this->total_earned;
        
        if ($totalEarned >= 10000) {
            return 'Diamante';
        } elseif ($totalEarned >= 5000) {
            return 'Oro';
        } elseif ($totalEarned >= 2000) {
            return 'Plata';
        } else {
            return 'Bronce';
        }
    }

    public function getNextLevelPoints()
    {
        $currentLevel = $this->level;
        
        switch ($currentLevel) {
            case 'Bronce':
                return 2000;
            case 'Plata':
                return 5000;
            case 'Oro':
                return 10000;
            case 'Diamante':
                return null; // Nivel máximo
            default:
                return 2000;
        }
    }

    public function getLevelProgress()
    {
        $nextLevel = $this->getNextLevelPoints();
        if (!$nextLevel) return 100;
        
        $currentTotal = $this->total_earned;
        $previousLevel = $this->getPreviousLevelPoints();
        
        $progress = (($currentTotal - $previousLevel) / ($nextLevel - $previousLevel)) * 100;
        return min(100, max(0, $progress));
    }

    private function getPreviousLevelPoints()
    {
        switch ($this->level) {
            case 'Bronce':
                return 0;
            case 'Plata':
                return 2000;
            case 'Oro':
                return 5000;
            case 'Diamante':
                return 10000;
            default:
                return 0;
        }
    }
}
