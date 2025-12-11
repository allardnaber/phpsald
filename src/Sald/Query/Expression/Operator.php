<?php

namespace Sald\Query\Expression;

enum Operator: string {

	case ANY = 'ANY';
	case ALL = 'ALL';
	case SOME = 'SOME';

}
