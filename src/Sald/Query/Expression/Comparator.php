<?php

namespace Sald\Query\Expression;

enum Comparator: string {
	case EQ = '=';
	case NEQ = '!=';
	case GT = '>';
	case GTE = '>=';
	case LT = '<';
	case LTE = '<=';
	case NULL = 'IS NULL';
	case NOTNULL = 'IS NOT NULL';
	case TRUE = 'IS TRUE';
	case NOTTRUE = 'IS NOT TRUE';
	case FALSE = 'IS FALSE';
	case NOTFALSE = 'IS NOT FALSE';
	case LIKE = 'LIKE';

}
