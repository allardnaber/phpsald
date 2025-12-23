<?php

namespace Sald\Connection\MultiHost;

enum TargetServerTypeValues: string {

	case ANY = 'any';
	case PRIMARY = 'primary';
	case SECONDARY = 'secondary';
	case PREFER_PRIMARY = 'preferPrimary';
	case PREFER_SECONDARY = 'preferSecondary';
}
