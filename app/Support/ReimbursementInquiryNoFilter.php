<?php

namespace App\Support;

use Illuminate\Http\Request;

/** Filter listing reimbursement by nomor inquiry (partial match) atau ID numerik. */
class ReimbursementInquiryNoFilter
{
    /**
     * @param  \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder
     */
    public static function apply($query, Request $request, string $noColumn = 'reimbursement.no_reimbursement', string $idColumn = 'reimbursement.id')
    {
        if (!$request->filled('inquiry_no')) {
            return $query;
        }

        $term = trim((string) $request->inquiry_no);
        if ($term === '') {
            return $query;
        }

        return $query->where(function ($q) use ($term, $noColumn, $idColumn) {
            $q->where($noColumn, 'like', '%' . $term . '%');
            if (ctype_digit($term)) {
                $q->orWhere($idColumn, (int) $term);
            }
        });
    }
}
