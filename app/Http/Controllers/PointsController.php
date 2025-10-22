<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\UserPoints;
use App\Models\PointsHistory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PointsController extends Controller
{
    public function getUserPoints($userId)
    {
        $userPoints = UserPoints::where('user_id', $userId)->first();
        
        if (!$userPoints) {
            // Crear registro de puntos si no existe
            $userPoints = UserPoints::create([
                'user_id' => $userId,
                'points_earned' => 0,
                'points_spent' => 0,
                'current_points' => 0,
                'level' => 'Bronce',
                'total_earned' => 0,
                'monthly_goal' => 1000,
                'monthly_progress' => 0
            ]);
        }

        return response()->json([
            'current_points' => $userPoints->current_points,
            'total_earned' => $userPoints->total_earned,
            'level' => $userPoints->level,
            'level_progress' => $userPoints->getLevelProgress(),
            'next_level_points' => $userPoints->getNextLevelPoints(),
            'monthly_goal' => $userPoints->monthly_goal,
            'monthly_progress' => $userPoints->monthly_progress
        ]);
    }

    public function getPointsHistory($userId)
    {
        $history = PointsHistory::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json($history);
    }

    public function addPointsFromPurchase(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:0',
            'description' => 'required|string'
        ]);

        // Calcular puntos: 1 punto por cada peso gastado
        $pointsToAdd = (int) $request->amount;

        DB::beginTransaction();
        try {
            // Obtener o crear registro de puntos del usuario
            $userPoints = UserPoints::where('user_id', $request->user_id)->first();
            
            if (!$userPoints) {
                $userPoints = UserPoints::create([
                    'user_id' => $request->user_id,
                    'points_earned' => 0,
                    'points_spent' => 0,
                    'current_points' => 0,
                    'level' => 'Bronce',
                    'total_earned' => 0,
                    'monthly_goal' => 1000,
                    'monthly_progress' => 0
                ]);
            }

            // Actualizar puntos
            $userPoints->points_earned += $pointsToAdd;
            $userPoints->current_points += $pointsToAdd;
            $userPoints->total_earned += $pointsToAdd;
            $userPoints->monthly_progress += $pointsToAdd;
            
            // Verificar si subió de nivel
            $newLevel = $userPoints->calculateLevel();
            if ($newLevel !== $userPoints->level) {
                $userPoints->level = $newLevel;
            }
            
            $userPoints->save();

            // Crear registro en historial
            PointsHistory::create([
                'user_id' => $request->user_id,
                'points' => $pointsToAdd,
                'type' => 'earned',
                'description' => $request->description,
                'source' => 'purchase',
                'amount' => $request->amount
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Puntos agregados exitosamente',
                'points_added' => $pointsToAdd,
                'current_points' => $userPoints->current_points,
                'new_level' => $userPoints->level
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['error' => 'Error al agregar puntos'], 500);
        }
    }

    public function redeemReward(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'reward_id' => 'required|integer',
            'points_required' => 'required|integer|min:1',
            'description' => 'required|string'
        ]);

        DB::beginTransaction();
        try {
            $userPoints = UserPoints::where('user_id', $request->user_id)->first();
            
            if (!$userPoints) {
                return response()->json(['error' => 'Usuario no encontrado'], 404);
            }

            if ($userPoints->current_points < $request->points_required) {
                return response()->json(['error' => 'Puntos insuficientes'], 400);
            }

            // Descontar puntos
            $userPoints->current_points -= $request->points_required;
            $userPoints->points_spent += $request->points_required;
            $userPoints->save();

            // Crear registro en historial
            PointsHistory::create([
                'user_id' => $request->user_id,
                'points' => -$request->points_required,
                'type' => 'spent',
                'description' => $request->description,
                'source' => 'reward'
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Recompensa canjeada exitosamente',
                'points_spent' => $request->points_required,
                'current_points' => $userPoints->current_points
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['error' => 'Error al canjear recompensa'], 500);
        }
    }
}
