<?php
/** @var User $user */
/** @var string $page */
global $rootURL;
?>
<div class="collapse navbar-collapse" id="navbarSupportedContent">
    <ul class="navbar-nav mb-2 dflex">
        <!-- Menu Items -->
        <!-- Home page -->
        <li class="nav-item">
            <a class="nav-link <?= $page=='home'?'active':'' ?>" href="<?= $rootURL ?>/">Dashboard</a>
        </li>
        <!-- /Home page -->
        <!-- Messaging Dropdown -->
        <li class="nav-item dropdown">
            <a class="nav-link <?= $page=='messages-index'||$page=='messages-log'?'active':''?>" href="#" id="messageDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                Messaging
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="messageDropdown">
                <li><a class="dropdown-item <?= $page=='messages-index'?'active':''?>" href="<?= $rootURL ?>/messages">All Messages</a></li>
                <li><a class="dropdown-item <?= $page=='messages-log'?'active':''?>" href="<?= $rootURL ?>/messages/log">Participant Message Log</a></li>
            </ul>
        </li>
        <!-- /Dropdown -->

        <!-- Survey link -->
        <li class="nav-item">
            <a class="nav-link <?= $page=='survey-view'?'active':''?>" aria-current="false" href="<?= $rootURL ?>/survey/view">Surveys</a>
        </li>
        <!-- /Survey link -->

        <!-- Participant Dropdown -->
        <li class="nav-item dropdown">
            <a class="nav-link <?= $page=='participants-index'||$page=='participants-state'?'active':''?>" href="#" id="participantDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                Participants
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="participantDropdown">
                <li><a class="dropdown-item <?= $page=='participants-index'?'active':''?>" href="<?= $rootURL ?>/participants">Management</a></li>
                <li><a class="dropdown-item <?= $page=='participants-state'?'active':''?>" href="<?= $rootURL ?>/participants/state-log">Audit Log</a></li>
            </ul>
        </li>
        <!-- /Dropdown -->

        <!-- Protocol Types-->
        <li class="nav-item">
            <a class="nav-link <?= $page=='protocol-types-index'?'active':''?>" aria-current="false" href="<?= $rootURL ?>/protocol-types">Protocol Types</a>
        </li>
        <!-- /Protocol Types -->

        <?php if (isset($user) && ($user->hasRole("Study Admin") || $user->hasRole("Super Admin"))) : ?>
            <li class="nav-item">
                <a class="nav-link <?= $page=='users'?'active':''?>" aria-current="false" href="<?= $rootURL ?>/users">Users</a>
            </li>
        <?php endif; ?>

        <!-- My Acct Dropdown -->
        <?php if ($user->getId() != ""): ?>
            <li class="nav-item dropdown ">
                <a class="nav-link" href="#" id="myAcctDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fa-regular fa-circle-user"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end acct-menu" aria-labelledby="myAcctDropdown">
                    <?php if (!is_null($user)) : ?>
                        <?php if (is_null($user->getFullName()) || empty($user->getFullName())) : ?>
                            <li class="nav-item"><p class="fw-bold"><?php echo $user->getEPPN(); ?></p></li>
                        <?php else : ?>
                            <li class="nav-item"><p class="fw-bold"><?php echo $user->getFullName(); ?></p></li>
                        <?php endif; ?>
                    <?php endif; ?>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="<?= $rootURL ?>/logout">Logout</a></li>
                </ul>
            </li>
        <?php endif; ?>
        <!-- /My Acct Dropdown -->
    </ul> <!-- /Menu Items -->
</div>