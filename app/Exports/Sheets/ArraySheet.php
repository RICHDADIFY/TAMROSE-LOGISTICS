<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;

class ArraySheet implements FromArray, WithTitle
{
    protected string $title;
    protected array $rows;
    protected ?array $headers;

    /**
     * @param string     $title    Sheet tab title
     * @param array      $rows     Array of associative rows (can be empty)
     * @param array|null $headers  Optional explicit headers; if null we'll infer from first row
     */
    public function __construct(string $title, array $rows, ?array $headers = null)
    {
        $this->title   = $title;
        $this->rows    = $rows;
        $this->headers = $headers;
    }

    public function array(): array
    {
        // Decide headers
        $headers = $this->headers;
        if (!$headers) {
            if (!empty($this->rows)) {
                $first = (array) (is_object($this->rows[0]) ? (array) $this->rows[0] : $this->rows[0]);
                $headers = array_keys($first);
            } else {
                // Fallback when there is no data at all
                return [['Info'], ['No data']];
            }
        }

        // Normalize rows to header order
        $out = [];
        $out[] = $headers;
        foreach ($this->rows as $r) {
            $rArr = (array) $r;
            $line = [];
            foreach ($headers as $h) {
                $line[] = array_key_exists($h, $rArr) ? $rArr[$h] : null;
            }
            $out[] = $line;
        }

        return $out;
    }

    public function title(): string
    {
        // Excel tab title limit = 31 and no : \ / ? * [ ]
        $t = preg_replace('/[:\\\\\\/\\?\\*\\[\\]]/', ' ', $this->title);
        return mb_substr($t, 0, 31);
    }
}
