<?php

namespace App\Services;

use App\Repositories\AiKnowledgeRepository;

/**
 * مرز مستقل Retrieval بین HTTP endpoint و Data Access.
 *
 * در این گام، رفتار جستجوی فعلی حفظ شده و فقط orchestration از endpoint
 * خارج می‌شود. در گام بعدی فاز 1، keyword extraction/scoring/context building
 * از Repository به همین لایه و سرویس‌های مرتبط منتقل می‌شوند.
 */
class KnowledgeRetriever
{
    private AiKnowledgeRepository $knowledgeRepository;
    private QueryPreprocessor $queryPreprocessor;

    public function __construct(
        ?AiKnowledgeRepository $knowledgeRepository = null,
        ?QueryPreprocessor $queryPreprocessor = null
    ) {
        $this->knowledgeRepository = $knowledgeRepository ?? new AiKnowledgeRepository();
        $this->queryPreprocessor = $queryPreprocessor ?? new QueryPreprocessor();
    }

    /**
     * @return string[] context blockهای آماده برای LlmService
     */
    public function retrieve(string $question, int $perTableLimit = 2): array
    {
        $normalizedQuestion = $this->queryPreprocessor->normalize($question);

        if ($normalizedQuestion === '') {
            return [];
        }

        return $this->knowledgeRepository->search($normalizedQuestion, $perTableLimit);
    }
}
