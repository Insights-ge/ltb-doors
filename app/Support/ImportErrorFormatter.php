<?php

namespace App\Support;

use App\Data\ImportErrorListData;
use App\Data\ImportRowErrorData;
use Illuminate\Support\HtmlString;

/**
 * Turns import errors into the lines shown in the preview and in the result notification.
 */
class ImportErrorFormatter
{
    /**
     * The intro line, one line per kept error ("Row 5: …"), and a "…and N more errors." line when errors were left out.
     */
    public function toHtml(string $intro, ImportErrorListData $errors): HtmlString
    {
        $lines = [e($intro)];

        foreach ($errors->errors as $error) {
            $lines[] = e($this->line($error));
        }

        $hiddenErrorCount = $errors->totalCount - count($errors->errors);

        if ($hiddenErrorCount > 0) {
            $lines[] = e(__('imports.more_errors', ['count' => $hiddenErrorCount]));
        }

        return new HtmlString(implode('<br>', $lines));
    }

    private function line(ImportRowErrorData $error): string
    {
        if ($error->rowNumber === null) {
            return $error->message;
        }

        return __('imports.row_error', ['row' => $error->rowNumber, 'message' => $error->message]);
    }
}
