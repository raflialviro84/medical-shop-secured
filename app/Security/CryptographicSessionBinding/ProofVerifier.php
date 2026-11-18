<?php

namespace App\Security\CryptographicSessionBinding;

use CryptographicSession\Services\ProofVerifier as BaseProofVerifier;

/**
 * Backward compatibility alias for the modular ProofVerifier service.
 */
class ProofVerifier extends BaseProofVerifier
{
}
