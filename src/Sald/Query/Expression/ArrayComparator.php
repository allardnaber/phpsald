<?php

namespace Sald\Query\Expression;

enum ArrayComparator: string {

	case IN = 'IN';
	case NOTIN = 'NOT IN';
}
