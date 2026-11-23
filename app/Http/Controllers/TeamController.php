<?php

namespace App\Http\Controllers;

use App\Data\PageData;
use App\Data\TeamMemberData;
use Inertia\Inertia;
use Inertia\Response;

/** The Team page: a short introduction in Markdown and a card for each member (config/team.php). */
class TeamController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('team', [
            'page' => PageData::load('team', app()->getLocale()),
            'members' => TeamMemberData::fromConfig(),
        ]);
    }
}
