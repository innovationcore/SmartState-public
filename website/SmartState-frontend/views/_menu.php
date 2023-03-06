<?php
/** @var UserSession $userSession */
/** @var string $page */
?>
        <header class="col-12 col-md-2 bg-light sidebar sidebar-sticky">
            <ul class="flex-row flex-md-column navbar-nav justify-content-between sidebar-menu sidebar-sticky">
                <li class="nav-item d-none d-md-inline">
                    <span class="nav-link sidebar-heading pl-0 text-nowrap">Messaging</span>
                </li>
                <li class="nav-item">
                    <a class="nav-link<?php if (!is_null($page) && $page == "messages-index") { echo " active"; } ?>" href="/messages" data-toggle="tooltip" data-placement="bottom" title="Messages overview">
                        <i class="fas fa-paper-plane"></i>
                        <span class="d-none d-md-inline">Overview<?php if (!is_null($page) && $page == "messages-index") { echo' <span class="sr-only">(current)</span>'; } ?></span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link<?php if (!is_null($page) && $page == "messages-log") { echo " active"; } ?>" href="/messages/log" data-toggle="tooltip" data-placement="bottom" title="Message Logs">
                        <i class="fas fa-list"></i>
                        <span class="d-none d-md-inline">Logs<?php if (!is_null($page) && $page == "messages-log") { echo' <span class="sr-only">(current)</span>'; } ?></span>
                    </a>
                </li>
                <li class="nav-item d-none d-md-inline">
                    <span class="nav-link sidebar-heading pl-0 text-nowrap">Surveys</span>
                </li>
                <li class="nav-item">
                    <a class="nav-link<?php if (!is_null($page) && $page == "survey-view") { echo " active"; } ?>" href="/survey/view" data-toggle="tooltip" data-placement="bottom" title="View completed surveys">
                        <i class="fas fa-list"></i>
                        <span class="d-none d-md-inline">View All Surveys<?php if (!is_null($page) && $page == "survey-view") { echo' <span class="sr-only">(current)</span>'; } ?></span>
                    </a>
                </li>
                <li class="nav-item d-none d-md-inline">
                    <span class="nav-link sidebar-heading pl-0 text-nowrap">Participants</span>
                </li>
                <li class="nav-item">
                    <a class="nav-link<?php if (!is_null($page) && $page == "participants-index") { echo " active"; } ?>" href="/participants" data-toggle="tooltip" data-placement="bottom" title="Participants overview">
                        <i class="fas fa-user"></i>
                        <span class="d-none d-md-inline">Overview<?php if (!is_null($page) && $page == "participants-index") { echo' <span class="sr-only">(current)</span>'; } ?></span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link<?php if (!is_null($page) && $page == "participants-state") { echo " active"; } ?>" href="/participants/state-log" data-toggle="tooltip" data-placement="bottom" title="Participant State Log">
                        <i class="fas fa-history"></i>
                        <span class="d-none d-md-inline">State Log<?php if (!is_null($page) && $page == "participants-state") { echo' <span class="sr-only">(current)</span>'; } ?></span>
                    </a>
                </li>

                <li class="nav-item d-none d-md-inline">
                    <span class="nav-link sidebar-heading pl-0 text-nowrap">Protocol Types</span>
                </li>
                <li class="nav-item">
                    <a class="nav-link<?php if (!is_null($page) && $page == "protocol-types-index") { echo " active"; } ?>" href="/protocol-types" data-toggle="tooltip" data-placement="bottom" title="Protocol types overview">
                        <i class="fas fa-book"></i>
                        <span class="d-none d-md-inline">Overview<?php if (!is_null($page) && $page == "protocol-types-index") { echo' <span class="sr-only">(current)</span>'; } ?></span>
                    </a>
                </li>
<?php if($userSession->getUser()->isAdmin()): ?> 
                <li class="nav-item d-none d-md-inline">
                    <span class="nav-link sidebar-heading pl-0 text-nowrap">Administration</span>
                </li>
                <li class="nav-item">
                    <a class="nav-link<?php if (!is_null($page) && $page == "users") { echo " active"; } ?>" href="/users" data-toggle="tooltip" data-placement="bottom" title="Manage User Admin Settings">
                        <i class="fas fa-users-cog"></i>
                        <span class="d-none d-md-inline">Users<?php if (!is_null($page) && $page == "users") { echo' <span class="sr-only">(current)</span>'; } ?></span>
                    </a>
                </li>
<?php endif; ?>
            </ul>
        </header>
    
        