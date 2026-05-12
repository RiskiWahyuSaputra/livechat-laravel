<?php

namespace App\Http\Controllers;

use App\Services\DomainWhitelistService;

class EmbedDocsController extends Controller
{
    protected DomainWhitelistService $whitelistService;

    public function __construct(DomainWhitelistService $whitelistService)
    {
        $this->whitelistService = $whitelistService;
    }

    /**
     * Tampilkan halaman dokumentasi embed widget.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $allowedDomains = $this->whitelistService->getAllowedDomains();
        $whitelistActive = !empty($allowedDomains);

        return view('embed-docs', [
            'whitelistActive' => $whitelistActive,
            'allowedDomains'  => $allowedDomains,
        ]);
    }
}
