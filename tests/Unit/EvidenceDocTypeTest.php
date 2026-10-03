<?php

namespace Tests\Unit;

use App\Support\TravelAttachmentResolver;
use Tests\TestCase;

/**
 * Oct 2026: each travel evidence file is marked either Invoice / Receipt (the
 * receipt an amount is claimed from, read by OCR) or Supporting Proof (an
 * emailed confirmation screenshot, ticket or assignment letter -- attached
 * only). The stored value drives whether OCR runs and which badge an approver
 * sees, so anything unrecognized must fall back to 'invoice' -- never silently
 * turn a receipt into un-OCR'd proof.
 */
class EvidenceDocTypeTest extends TestCase
{
    public function test_proof_is_recognized(): void
    {
        $this->assertSame('proof', TravelAttachmentResolver::normalizeDocType('proof'));
    }

    public function test_proof_is_case_and_whitespace_insensitive(): void
    {
        // The value round-trips through a <select>, a hidden input and the DB,
        // so casing/padding must not decide whether a file gets OCR'd.
        $this->assertSame('proof', TravelAttachmentResolver::normalizeDocType('PROOF'));
        $this->assertSame('proof', TravelAttachmentResolver::normalizeDocType('Proof'));
        $this->assertSame('proof', TravelAttachmentResolver::normalizeDocType('  proof  '));
    }

    public function test_invoice_is_recognized(): void
    {
        $this->assertSame('invoice', TravelAttachmentResolver::normalizeDocType('invoice'));
        $this->assertSame('invoice', TravelAttachmentResolver::normalizeDocType('INVOICE'));
    }

    public function test_legacy_and_missing_values_read_as_invoice(): void
    {
        // Every attachment saved before doc_type existed: it was treated as an
        // invoice/receipt, and must keep reading that way.
        $this->assertSame('invoice', TravelAttachmentResolver::normalizeDocType(null));
        $this->assertSame('invoice', TravelAttachmentResolver::normalizeDocType(''));
        $this->assertSame('invoice', TravelAttachmentResolver::normalizeDocType('   '));
    }

    public function test_unrecognized_values_default_to_invoice_not_proof(): void
    {
        // Defaulting the other way would quietly skip OCR on a real receipt.
        $this->assertSame('invoice', TravelAttachmentResolver::normalizeDocType('nonsense'));
        $this->assertSame('invoice', TravelAttachmentResolver::normalizeDocType('bukti'));
        $this->assertSame('invoice', TravelAttachmentResolver::normalizeDocType('0'));
        $this->assertSame('invoice', TravelAttachmentResolver::normalizeDocType(123));
    }
}
