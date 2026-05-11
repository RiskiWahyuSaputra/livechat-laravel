@extends('layouts.shop')

@section('title', 'Kategori: ' . $category->name)

@section('content')
<div class="breadcrumb-container">
    <ol class="breadcrumb-list">
        <li class="breadcrumb-item">
            <a href="{{ route('user.home') }}" class="breadcrumb-link">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="breadcrumb-home-icon"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
            </a>
        </li>
        <li class="breadcrumb-item">
            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="breadcrumb-separator"><polyline points="9 18 15 12 9 6"/></svg>
            <a href="{{ route('user.home') }}" class="breadcrumb-link">Beranda</a>
        </li>
        <li class="breadcrumb-item">
            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="breadcrumb-separator"><polyline points="9 18 15 12 9 6"/></svg>
            <span class="breadcrumb-current">{{ $category->name }}</span>
        </li>
    </ol>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="row row-cols-1 row-cols-md-3 g-4 mb-5">
            @forelse($products as $product)
            <div class="col aos" data-aos="fade-up">
                <div class="card h-100 border-0 shadow-sm hero-main-card" style="padding: 20px;">
                    @if($product->image)
                        <img src="{{ asset($product->image) }}" class="card-img-top rounded-3" alt="{{ $product->name }}" style="height: 200px; object-fit: cover;">
                    @endif
                    <div class="card-body px-0 pb-0">
                        <h5 class="fw-bold mb-2">{{ $product->name }}</h5>
                        <p class="text-muted small mb-3">{{ Str::limit($product->description, 100) }}</p>
                        <div class="d-flex align-items-center justify-content-between mt-auto">
                            <span class="fs-5 fw-bold text-primary">Rp {{ number_format($product->price, 0, ',', '.') }}</span>
                            <button class="btn btn-primary rounded-pill btn-sm px-3">Detail</button>
                        </div>
                    </div>
                </div>
            </div>
            @empty
            <div class="col-12 text-center py-5">
                <div class="shimmer-badge mb-3">Produk Kosong</div>
                <p class="text-muted">Maaf, belum ada produk yang tersedia dalam kategori ini.</p>
                <a href="{{ route('user.home') }}" class="btn btn-outline-primary rounded-pill mt-3">Kembali ke Beranda</a>
            </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
