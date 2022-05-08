<?php

/*$ordersByDate['month'] = Order::select(DB::raw("MONTH(created_at)") , DB::raw("(COUNT(*)) as total_orders"))
            ->where(DB::raw("MONTH(created_at)") , $now->month)
            ->where(DB::raw("YEAR(created_at)") , $now->year)
            ->orderBy('created_at')
            ->groupBy(DB::raw("MONTH(created_at)") , DB::raw("YEAR(created_at)"))
            ->get();*/

/*$ordersByDate['year'] = Order::select(DB::raw("YEAR(created_at)") , DB::raw("(COUNT(*)) as total_orders"))
    ->where(DB::raw("YEAR(created_at)") , $now->year)
    ->orderBy('created_at')
    ->groupBy(DB::raw("YEAR(created_at)"))
    ->get();*/

?>