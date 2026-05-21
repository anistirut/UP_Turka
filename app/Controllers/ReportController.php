<?php

require_once __DIR__ . '/../Contexts/ReportContext.php';

class ReportController
{
    private ReportContext $ctx;

    public function __construct(ReportContext $ctx)
    {
        $this->ctx = $ctx;
    }

    /**
     * Агрегирует аналитику одним запросом через кэшированный метод контекста.
     */
    public function fetchAnalytics(): array
    {
        return $this->ctx->fetchAnalyticsCombined();
    }
}
