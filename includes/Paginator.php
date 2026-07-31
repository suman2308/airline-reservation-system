<?php
/**
 * AeroBook – Reusable Paginator
 *
 * Generates LIMIT/OFFSET values, page numbers, and Bootstrap 5 pagination HTML.
 * Usage:
 *   $paginator = new Paginator($totalRows, $perPage);
 *   $offset = $paginator->offset();
 *   $sql .= " LIMIT {$paginator->perPage()} OFFSET {$offset}";
 *   echo $paginator->render();
 */

class Paginator {
    private int $totalItems;
    private int $perPage;
    private int $currentPage;
    private int $totalPages;

    /**
     * @param int $totalItems Total number of items across all pages
     * @param int $perPage Items per page (default 20)
     * @param string $pageParam GET parameter name for the page number
     */
    public function __construct(
        int $totalItems,
        int $perPage = 20,
        private string $pageParam = 'page'
    ) {
        $this->totalItems = max(0, $totalItems);
        $this->perPage = max(1, $perPage);
        $this->totalPages = max(1, (int)ceil($this->totalItems / $this->perPage));
        $this->currentPage = max(1, min($this->totalPages, (int)($_GET[$this->pageParam] ?? 1)));
    }

    /**
     * SQL offset value for the current page.
     */
    public function offset(): int {
        return ($this->currentPage - 1) * $this->perPage;
    }

    /**
     * Items per page.
     */
    public function perPage(): int {
        return $this->perPage;
    }

    /**
     * Current page number.
     */
    public function currentPage(): int {
        return $this->currentPage;
    }

    /**
     * Total number of pages.
     */
    public function totalPages(): int {
        return $this->totalPages;
    }

    /**
     * Total number of items.
     */
    public function totalItems(): int {
        return $this->totalItems;
    }

    /**
     * Starting record number on the current page (1-based).
     */
    public function firstItem(): int {
        return $this->offset() + 1;
    }

    /**
     * Ending record number on the current page (1-based).
     */
    public function lastItem(): int {
        return min($this->offset() + $this->perPage, $this->totalItems);
    }

    /**
     * Returns true if there are enough items to paginate.
     */
    public function hasPages(): bool {
        return $this->totalPages > 1;
    }

    /**
     * Returns true if there is a previous page.
     */
    public function hasPrevious(): bool {
        return $this->currentPage > 1;
    }

    /**
     * Returns true if there is a next page.
     */
    public function hasNext(): bool {
        return $this->currentPage < $this->totalPages;
    }

    /**
     * Preserve existing query parameters except the page parameter.
     */
    private function buildUrl(int $page): string {
        $params = $_GET;
        $params[$this->pageParam] = $page;
        return '?' . http_build_query($params);
    }

    /**
     * Render Bootstrap 5 pagination HTML.
     */
    public function render(): string {
        if (!$this->hasPages()) {
            return '';
        }

        $html = '<nav aria-label="Page navigation"><ul class="pagination pagination-sm justify-content-center">';

        // Previous
        if ($this->hasPrevious()) {
            $html .= '<li class="page-item"><a class="page-link" href="' . $this->buildUrl($this->currentPage - 1) . '" aria-label="Previous"><i class="bi bi-chevron-left"></i></a></li>';
        } else {
            $html .= '<li class="page-item disabled"><span class="page-link"><i class="bi bi-chevron-left"></i></span></li>';
        }

        // Page numbers
        $start = max(1, $this->currentPage - 2);
        $end = min($this->totalPages, $this->currentPage + 2);

        if ($start > 1) {
            $html .= '<li class="page-item"><a class="page-link" href="' . $this->buildUrl(1) . '">1</a></li>';
            if ($start > 2) {
                $html .= '<li class="page-item disabled"><span class="page-link">…</span></li>';
            }
        }

        for ($i = $start; $i <= $end; $i++) {
            if ($i === $this->currentPage) {
                $html .= '<li class="page-item active"><span class="page-link">' . $i . '</span></li>';
            } else {
                $html .= '<li class="page-item"><a class="page-link" href="' . $this->buildUrl($i) . '">' . $i . '</a></li>';
            }
        }

        if ($end < $this->totalPages) {
            if ($end < $this->totalPages - 1) {
                $html .= '<li class="page-item disabled"><span class="page-link">…</span></li>';
            }
            $html .= '<li class="page-item"><a class="page-link" href="' . $this->buildUrl($this->totalPages) . '">' . $this->totalPages . '</a></li>';
        }

        // Next
        if ($this->hasNext()) {
            $html .= '<li class="page-item"><a class="page-link" href="' . $this->buildUrl($this->currentPage + 1) . '" aria-label="Next"><i class="bi bi-chevron-right"></i></a></li>';
        } else {
            $html .= '<li class="page-item disabled"><span class="page-link"><i class="bi bi-chevron-right"></i></span></li>';
        }

        $html .= '</ul></nav>';
        return $html;
    }
}
