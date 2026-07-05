<?php

namespace App\Services;

/**
 * Human labels and categories for common Elite Dangerous binding commands.
 */
final class ControlDictionary
{
    /** @var array<string,string> */
    private const LABELS = [
        'YawLeftButton' => 'Yaw Left',
        'YawRightButton' => 'Yaw Right',
        'RollLeftButton' => 'Roll Left',
        'RollRightButton' => 'Roll Right',
        'PitchUpButton' => 'Pitch Up',
        'PitchDownButton' => 'Pitch Down',
        'ForwardKey' => 'Forward Thrust',
        'BackwardKey' => 'Reverse Thrust',
        'LeftThrustButton' => 'Thrust Left',
        'RightThrustButton' => 'Thrust Right',
        'UpThrustButton' => 'Thrust Up',
        'DownThrustButton' => 'Thrust Down',
        'EngineColourToggle' => 'Engine Colour',
        'SetSpeedMinus100' => '100% Reverse',
        'SetSpeedMinus50' => '50% Reverse',
        'SetSpeedZero' => 'All Stop',
        'SetSpeed50' => '50% Throttle',
        'SetSpeed75' => '75% Throttle',
        'SetSpeed100' => '100% Throttle',
        'UseBoostJuice' => 'Boost',
        'HyperSuperCombination' => 'Hyperspace/Supercruise',
        'Supercruise' => 'Supercruise',
        'Hyperspace' => 'Hyperspace',
        'TargetNextRouteSystem' => 'Next Jump Dest',
        'PrimaryFire' => 'Primary Fire',
        'SecondaryFire' => 'Secondary Fire',
        'DeployHardpointToggle' => 'Hardpoints',
        'CycleFireGroupNext' => 'Next Fire Group',
        'CycleFireGroupPrevious' => 'Previous Fire Group',
        'SelectTarget' => 'Target Ahead',
        'CycleNextTarget' => 'Next Contact',
        'CyclePreviousTarget' => 'Previous Contact',
        'TargetWingman0' => 'Wingman 1',
        'TargetWingman1' => 'Wingman 2',
        'TargetWingman2' => 'Wingman 3',
        'TargetWingmanNavLock' => 'Wingman Navlock',
        'CycleNextSubsystem' => 'Next Subsystem',
        'CyclePreviousSubsystem' => 'Previous Subsystem',
        'ChaffLauncher' => 'Chaff',
        'ShieldCellBank' => 'SCB',
        'HeatSinkLauncher' => 'Heatsink',
        'SilentRunning' => 'Silent Running',
        'NightVisionToggle' => 'Night Vision',
        'ToggleCargoScoop' => 'Cargo Scoop',
        'LandingGearToggle' => 'Landing Gear',
        'ShipSpotLightToggle' => 'Lights',
        'HeadLookToggle' => 'Head Look',
        'MicrophoneMute' => 'Microphone',
        'QuickCommsPanel' => 'Quick Comms',
        'FocusCommsPanel' => 'Comms Panel',
        'FocusLeftPanel' => 'Nav Panel',
        'FocusRadarPanel' => 'Role Panel',
        'FocusRightPanel' => 'Systems Panel',
        'GalaxyMapOpen' => 'Galaxy Map',
        'SystemMapOpen' => 'System Map',
        'OpenCodexGoToDiscovery' => 'Codex',
        'OrbitLinesToggle' => 'Orbit Lines',
        'ToggleFlightAssist' => 'Flight Assist',
        'ToggleRotationalCorrection' => 'Rotational Correction',
        'EjectAllCargo' => 'Eject All Cargo',
        'ResetHMDOrientation' => 'Reset HMD',
        'Pause' => 'Main Menu',
        'IncreaseEnginesPower' => 'ENG',
        'IncreaseWeaponsPower' => 'WEP',
        'IncreaseSystemsPower' => 'SYS',
        'ResetPowerDistribution' => 'RST',
        'SteerLeftButton' => 'Steer Left',
        'SteerRightButton' => 'Steer Right',
        'BuggyRollLeftButton' => 'Roll Left',
        'BuggyRollRightButton' => 'Roll Right',
        'BuggyPitchUpButton' => 'Pitch Up',
        'BuggyPitchDownButton' => 'Pitch Down',
        'VerticalThrustersButton' => 'Vertical Thrusters',
        'BuggyPrimaryFireButton' => 'Primary Fire',
        'BuggySecondaryFireButton' => 'Secondary Fire',
        'BuggyTurretYawLeftButton' => 'Turret Left',
        'BuggyTurretYawRightButton' => 'Turret Right',
        'BuggyTurretPitchUpButton' => 'Turret Up',
        'BuggyTurretPitchDownButton' => 'Turret Down',
        'DriveAssist' => 'Drive Assist',
        'BuggyToggleReverseThrottleInput' => 'Reverse',
        'BuggyHandbrake' => 'Handbrake',
        'BuggySetSpeedZero' => 'Zero Speed',
        'BuggySetSpeed100' => 'Maximum Speed',
        'ExplorationFSSDiscoveryScan' => 'Enter FSS',
        'ExplorationFSSQuit' => 'Exit FSS',
        'ExplorationFSSTarget' => 'Target FSS',
        'ExplorationFSSZoomIn' => 'Step Zoom FSS In',
        'ExplorationFSSZoomOut' => 'Step Zoom FSS Out',
        'ExplorationFSSCameraPitchIncreaseButton' => 'FSS Pitch Up',
        'ExplorationFSSCameraPitchDecreaseButton' => 'FSS Pitch Down',
        'ExplorationFSSCameraYawIncreaseButton' => 'FSS Yaw Right',
        'ExplorationFSSCameraYawDecreaseButton' => 'FSS Yaw Left',
        'ExplorationFSSRadioTuningX_Increase' => 'FSS Tune Right',
        'ExplorationFSSRadioTuningX_Decrease' => 'FSS Tune Left',
        'ExplorationFSSShowHelp' => 'FSS Help',
        'ExplorationSAAChangeScannedAreaViewToggle' => 'Toggle DSS View',
        'ExplorationSAANextGenus' => 'Next Signal DSS',
        'ExplorationSAAPreviousGenus' => 'Prev Signal DSS',
        'ExplorationSAAExitThirdPerson' => 'Exit DSS',
        'UI_Up' => 'UI Up',
        'UI_Down' => 'UI Down',
        'UI_Left' => 'UI Left',
        'UI_Right' => 'UI Right',
        'UI_Select' => 'UI Select',
        'UI_Back' => 'UI Back',
        'UI_Toggle' => 'UI Toggle',
        'CycleNextPanel' => 'Next Panel',
        'CyclePreviousPanel' => 'Prev Panel',
        'CycleNextPage' => 'Next Page',
        'CyclePreviousPage' => 'Prev Page',
        'CommanderCreator_Undo' => 'Undo Holo-Me',
        'CommanderCreator_Redo' => 'Redo Holo-Me',
        'PhotoCameraToggle' => 'External Cam',
        'VanityCameraScrollLeft' => 'Prev Cam',
        'VanityCameraScrollRight' => 'Next Cam',
        'FreeCamToggle' => 'Free Cam',
        'FreeCamZoomIn' => 'Zoom In',
        'FreeCamZoomOut' => 'Zoom Out',
        'FreeCamTranslateForward' => 'Cam Forwards',
        'FreeCamTranslateBackward' => 'Cam Backwards',
        'FreeCamTranslateLeft' => 'Cam Left',
        'FreeCamTranslateRight' => 'Cam Right',
        'FreeCamTranslateUp' => 'Cam Up',
        'FreeCamTranslateDown' => 'Cam Down',
        'FreeCamYawLeft' => 'Cam Yaw Left',
        'FreeCamYawRight' => 'Cam Yaw Right',
        'FreeCamPitchUp' => 'Cam Pitch Up',
        'FreeCamPitchDown' => 'Cam Pitch Down',
        'FreeCamRollLeft' => 'Cam Roll Left',
        'FreeCamRollRight' => 'Cam Roll Right',
        'FreeCamIncreaseSpeed' => 'Inc Cam Speed',
        'FreeCamDecreaseSpeed' => 'Dec Cam Speed',
        'FreeCamToggleHUD' => 'Toggle HUD',
        'FocusPhotoCamera' => 'Photo Camera',
        'CamPitchUp' => 'Cam Cockpit Up',
        'CamPitchDown' => 'Cam Cockpit Down',
        'CamYawLeft' => 'Cam Cockpit Left',
        'CamYawRight' => 'Cam Cockpit Right',
        'OrderDefensiveBehaviour' => 'Be Defensive',
        'OrderAggressiveBehaviour' => 'Be Aggressive',
        'OrderFocusTarget' => 'Attack My Target',
        'OrderHoldFire' => 'Hold Fire',
        'OrderHoldPosition' => 'Hold Position',
        'OrderFollow' => 'Follow',
        'RolePanelMode' => 'Multicrew Mode',
        'HumanoidForwardButton' => 'Fwd',
        'HumanoidBackwardButton' => 'Back',
        'HumanoidStrafeLeftButton' => 'Strafe Left',
        'HumanoidStrafeRightButton' => 'Strafe Right',
        'HumanoidSprintButton' => 'Sprint',
        'HumanoidCrouchButton' => 'Crouch',
        'HumanoidJumpButton' => 'Jump',
        'HumanoidPrimaryInteractButton' => 'Interact',
        'HumanoidSecondaryInteractButton' => 'Interact 2',
        'HumanoidItemWheelButton' => 'Item Wheel',
        'HumanoidOpenAccessPanelButton' => 'Open Access Panel',
        'HumanoidSwitchToRechargeTool' => 'Recharge Tool',
        'HumanoidSwitchToCompAnalyser' => 'Comp Analyser',
        'HumanoidSwitchToSuitTool' => 'Suit Tool',
        'HumanoidSelectPrimaryWeaponButton' => 'WEP 1',
        'HumanoidSelectSecondaryWeaponButton' => 'WEP 2',
        'HumanoidReloadButton' => 'Reload',
        'HumanoidToggleFlashlightButton' => 'Flashlight',
        'HumanoidHolsterButton' => 'Holster',
        'HumanoidThrowGrenadeButton' => 'Throw Grenade',
        'HumanoidMeleeButton' => 'Melee',
        'HumanoidUtilityWheelCycleMode' => 'Tool Mode',
        'GalaxyMapHome' => 'GalMap Home',
        'GalaxyMapOpen' => 'GalMap',
        'GalaxyMapPitchUp' => 'GalMap Pitch Up',
        'GalaxyMapPitchDown' => 'GalMap Pitch Down',
        'GalaxyMapYawLeft' => 'GalMap Yaw Left',
        'GalaxyMapYawRight' => 'GalMap Yaw Right',
        'GalaxyMapTranslateForward' => 'GalMap Forward',
        'GalaxyMapTranslateBackward' => 'GalMap Backward',
        'GalaxyMapTranslateLeft' => 'GalMap Left',
        'GalaxyMapTranslateRight' => 'GalMap Right',
        'GalaxyMapTranslateUp' => 'GalMap Up',
        'GalaxyMapTranslateDown' => 'GalMap Down',
        'GalaxyMapZoomIn' => 'GalMap Zoom In',
        'GalaxyMapZoomOut' => 'GalMap Zoom Out',
    ];

    /** @var array<string,string> */
    private const CATEGORY_OVERRIDES = [
        'GalaxyMapHome' => 'Galaxy Map',
        'GalaxyMapOpen' => 'Galaxy Map',
        'SystemMapOpen' => 'Ship',
        'UI_Up' => 'UI',
        'UI_Down' => 'UI',
        'UI_Left' => 'UI',
        'UI_Right' => 'UI',
        'UI_Select' => 'UI',
        'UI_Back' => 'UI',
        'UI_Toggle' => 'UI',
        'CommanderCreator_Undo' => 'Holo-Me',
        'CommanderCreator_Redo' => 'Holo-Me',
    ];

    /**
     * Return a readable control label.
     *
     * @param string $command Internal command name.
     * @return string Human label.
     */
    public function label(string $command): string
    {
        return self::LABELS[$command] ?? $this->prettifyCommand($command);
    }

    /**
     * Return a category for a command.
     *
     * @param string $command Internal command name.
     * @return string Category name.
     */
    public function category(string $command): string
    {
        if (isset(self::CATEGORY_OVERRIDES[$command])) {
            return self::CATEGORY_OVERRIDES[$command];
        }

        $haystack = strtolower($command);
        return match (true) {
            str_contains($haystack, 'galaxymap') => 'Galaxy Map',
            str_contains($haystack, 'buggy') || str_contains($haystack, 'srv') || str_contains($haystack, 'driveassist') || str_contains($haystack, 'steer') => 'SRV',
            str_contains($haystack, 'exploration') || str_contains($haystack, 'fss') || str_contains($haystack, 'saa') || str_contains($haystack, 'discoveryscan') => 'Scanners',
            str_contains($haystack, 'fighter') || str_contains($haystack, 'orders') || str_contains($haystack, 'order') => 'Fighter',
            str_contains($haystack, 'humanoid') || str_contains($haystack, 'onfoot') || str_contains($haystack, 'grenade') || str_contains($haystack, 'suit') => 'On Foot',
            str_contains($haystack, 'multicrew') || str_contains($haystack, 'rolepanelmode') => 'Multicrew',
            str_contains($haystack, 'camera') || str_contains($haystack, 'cam') || str_contains($haystack, 'freecam') || str_contains($haystack, 'vanity') || str_contains($haystack, 'photo') => 'Camera',
            str_contains($haystack, 'commandercreator') || str_contains($haystack, 'holo') => 'Holo-Me',
            str_starts_with($command, 'UI_') || str_contains($haystack, 'panel') || str_contains($haystack, 'focus') => 'UI',
            str_contains($haystack, 'nightvision') || str_contains($haystack, 'microphone') || str_contains($haystack, 'hmd') || str_contains($haystack, 'codex') || str_contains($haystack, 'pause') => 'Misc',
            default => 'Ship',
        };
    }

    /**
     * Convert an unknown command identifier to readable text.
     *
     * @param string $command Internal command name.
     * @return string Readable command.
     */
    private function prettifyCommand(string $command): string
    {
        $value = str_replace(['_', 'Button', 'Toggle'], [' ', '', ''], $command);
        $value = preg_replace('/(?<!^)([A-Z])/', ' $1', $value) ?: $value;
        $value = preg_replace('/\s+/', ' ', $value) ?: $value;
        return trim($value);
    }
}
