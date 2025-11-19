<?php

namespace Obsidiane\AuthBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Bundle Symfony minimal permettant d'enregistrer le SDK comme dépendance.
 *
 * Il n'enregistre pas de configuration automatique : instanciez simplement
 * Obsidiane\AuthBundle\AuthClient avec les paramètres souhaités, ou ajoutez
 * votre propre configuration de service si nécessaire.
 */
final class ObsidianeAuthBundle extends Bundle
{
}

