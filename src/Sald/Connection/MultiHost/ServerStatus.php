<?php

namespace Sald\Connection\MultiHost;

enum ServerStatus {
	case PRIMARY;
	case SECONDARY;
	case UNAVAILABLE;
}
