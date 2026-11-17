<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Mengambil kategori member untuk seluruh halaman Customer
        View::composer('customer.layouts.app', function ($view) {

            if (Auth::check()) {
                $hasilMember = DB::selectOne(
                    'SELECT fn_cek_kategori_member(?) AS kategori_member',
                    [Auth::id()]
                );

                $kategoriMember = $hasilMember->kategori_member;
            } else {
                $kategoriMember = null;
            }

            $view->with('kategoriMember', $kategoriMember);
        });
    }
}
