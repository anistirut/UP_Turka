<?php

require_once __DIR__ . '/../Contexts/ReportContext.php';

class ReportController
{
    private ReportContext $ctx;

    public function __construct(ReportContext $ctx)
    {
        $this->ctx = $ctx;
    }

    public function fetchAnalytics(): array
    {
        return [
            'avg_check'    => $this->ctx->fetchAvgCheck(),
            'popular'      => $this->ctx->fetchPopularDish(),
            'count_orders' => $this->ctx->fetchOrdersCount(),
        ];
    }
}
