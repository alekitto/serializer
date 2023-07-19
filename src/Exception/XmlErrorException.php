<?php

declare(strict_types=1);

namespace Kcs\Serializer\Exception;

use LibXMLError;
use Throwable;

use function sprintf;

use const LIBXML_ERR_ERROR;
use const LIBXML_ERR_FATAL;
use const LIBXML_ERR_WARNING;

class XmlErrorException extends RuntimeException
{
    private LibXMLError $xmlError;

    public function __construct(LibXMLError $error, Throwable|null $previous = null)
    {
        switch ($error->level) {
            case LIBXML_ERR_WARNING:
                $level = 'WARNING';
                break;

            case LIBXML_ERR_FATAL:
                $level = 'FATAL';
                break;

            case LIBXML_ERR_ERROR:
                $level = 'ERROR';
                break;

            default:
                $level = 'UNKNOWN';
        }

        parent::__construct(sprintf('[%s] %s in %s (line: %d, column: %d)', $level, $error->message, $error->file, $error->line, $error->column), 0, $previous);

        $this->xmlError = $error;
    }

    public function getXmlError(): LibXMLError
    {
        return $this->xmlError;
    }
}
