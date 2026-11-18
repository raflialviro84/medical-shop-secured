<?php

namespace CryptographicSession\Contracts;

use Illuminate\Http\Request;

interface ProofVerifierInterface
{
    /**
     * Verifikasi cryptographic proof terhadap HTTP request.
     *
     * @param Request $request
     * @param string $proof
     * @param string|null $expectedMethod
     * @param string|null $expectedUri
     * @return array{valid: bool, status?: int, message?: string, binding_id?: int}
     */
    public function verify(
        Request $request,
        string $proof,
        ?string $expectedMethod = null,
        ?string $expectedUri = null
    ): array;
}

