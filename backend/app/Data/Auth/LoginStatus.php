<?php

namespace App\Data\Auth;

enum LoginStatus
{
    case Success;
    case InvalidCredentials;
    case RateLimited;
}
