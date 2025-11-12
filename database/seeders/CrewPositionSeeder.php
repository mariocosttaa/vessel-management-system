<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CrewPositionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Creates global crew positions (vessel_id = null) that can be used across all vessels.
     * These positions follow maritime industry standards and align with the vessel
     * role-based access control system.
     */
    public function run(): void
    {
        $positions = [
            // Deck Department - Command & Navigation
            [
                'name' => 'Captain',
                'description' => 'Master of the vessel, responsible for overall command, navigation, safety, and operations. Has ultimate authority and responsibility for the vessel and crew.',
                'vessel_id' => null, // Global position
                'vessel_role_access_id' => null, // Can be assigned per vessel
            ],
            [
                'name' => 'First Officer',
                'description' => 'Second in command, assists the captain in navigation, deck operations, and crew management. Takes command when captain is unavailable.',
                'vessel_id' => null,
                'vessel_role_access_id' => null,
            ],
            [
                'name' => 'Second Officer',
                'description' => 'Navigation officer responsible for watchkeeping, chart maintenance, and navigation equipment. Often handles communications and safety equipment.',
                'vessel_id' => null,
                'vessel_role_access_id' => null,
            ],
            [
                'name' => 'Third Officer',
                'description' => 'Junior navigation officer responsible for watchkeeping duties, safety inspections, and assisting with navigation and deck operations.',
                'vessel_id' => null,
                'vessel_role_access_id' => null,
            ],
            [
                'name' => 'Chief Mate',
                'description' => 'Senior deck officer responsible for cargo operations, deck maintenance, and supervising deck crew. Acts as second-in-command.',
                'vessel_id' => null,
                'vessel_role_access_id' => null,
            ],
            [
                'name' => 'Bosun',
                'description' => 'Boatswain, senior deck crew member responsible for supervising deckhands, deck maintenance, and cargo handling operations.',
                'vessel_id' => null,
                'vessel_role_access_id' => null,
            ],
            [
                'name' => 'Able Seaman',
                'description' => 'Experienced deckhand with navigation and seamanship skills. Performs watchkeeping, maintenance, and cargo operations.',
                'vessel_id' => null,
                'vessel_role_access_id' => null,
            ],
            [
                'name' => 'Ordinary Seaman',
                'description' => 'Entry-level deck crew member. Assists with deck maintenance, mooring operations, and general vessel upkeep.',
                'vessel_id' => null,
                'vessel_role_access_id' => null,
            ],
            [
                'name' => 'Deckhand',
                'description' => 'General deck crew member responsible for maintenance, cleaning, mooring, and assisting with deck operations.',
                'vessel_id' => null,
                'vessel_role_access_id' => null,
            ],

            // Engine Department
            [
                'name' => 'Chief Engineer',
                'description' => 'Senior engineer responsible for all engine room operations, machinery maintenance, and technical systems. Reports directly to captain.',
                'vessel_id' => null,
                'vessel_role_access_id' => null,
            ],
            [
                'name' => 'Second Engineer',
                'description' => 'Senior engine officer responsible for watchkeeping, maintenance scheduling, and supervising engine room crew.',
                'vessel_id' => null,
                'vessel_role_access_id' => null,
            ],
            [
                'name' => 'Third Engineer',
                'description' => 'Engine officer responsible for watchkeeping, routine maintenance, and operating engine room equipment.',
                'vessel_id' => null,
                'vessel_role_access_id' => null,
            ],
            [
                'name' => 'Fourth Engineer',
                'description' => 'Junior engine officer responsible for watchkeeping duties, assisting with maintenance, and engine room operations.',
                'vessel_id' => null,
                'vessel_role_access_id' => null,
            ],
            [
                'name' => 'Engineer',
                'description' => 'Licensed engineer responsible for engine room operations, machinery maintenance, and technical systems.',
                'vessel_id' => null,
                'vessel_role_access_id' => null,
            ],
            [
                'name' => 'Mechanic',
                'description' => 'Skilled technician responsible for maintenance and repair of vessel machinery, engines, and mechanical systems.',
                'vessel_id' => null,
                'vessel_role_access_id' => null,
            ],
            [
                'name' => 'Electrician',
                'description' => 'Electrical technician responsible for electrical systems, wiring, generators, and electrical equipment maintenance.',
                'vessel_id' => null,
                'vessel_role_access_id' => null,
            ],
            [
                'name' => 'Oiler',
                'description' => 'Engine room crew member responsible for lubricating machinery, assisting with maintenance, and engine room operations.',
                'vessel_id' => null,
                'vessel_role_access_id' => null,
            ],
            [
                'name' => 'Wiper',
                'description' => 'Entry-level engine room crew member responsible for cleaning, basic maintenance, and assisting engineers.',
                'vessel_id' => null,
                'vessel_role_access_id' => null,
            ],

            // Galley & Service Department
            [
                'name' => 'Cook',
                'description' => 'Galley cook responsible for preparing meals, managing food inventory, and maintaining galley cleanliness and safety.',
                'vessel_id' => null,
                'vessel_role_access_id' => null,
            ],
            [
                'name' => 'Chief Cook',
                'description' => 'Senior cook responsible for meal planning, food preparation, galley management, and supervising galley staff.',
                'vessel_id' => null,
                'vessel_role_access_id' => null,
            ],
            [
                'name' => 'Steward',
                'description' => 'Service crew member responsible for cleaning, housekeeping, serving meals, and maintaining living quarters.',
                'vessel_id' => null,
                'vessel_role_access_id' => null,
            ],
            [
                'name' => 'Chief Steward',
                'description' => 'Senior steward responsible for managing service staff, inventory, and overall vessel housekeeping and service operations.',
                'vessel_id' => null,
                'vessel_role_access_id' => null,
            ],

            // Specialized Positions
            [
                'name' => 'Radio Officer',
                'description' => 'Communications specialist responsible for radio operations, emergency communications, and electronic navigation equipment.',
                'vessel_id' => null,
                'vessel_role_access_id' => null,
            ],
            [
                'name' => 'Purser',
                'description' => 'Administrative officer responsible for financial records, payroll, inventory management, and administrative duties.',
                'vessel_id' => null,
                'vessel_role_access_id' => null,
            ],
            [
                'name' => 'Carpenter',
                'description' => 'Skilled craftsman responsible for woodwork, repairs, and maintenance of vessel structures and fittings.',
                'vessel_id' => null,
                'vessel_role_access_id' => null,
            ],
            [
                'name' => 'Welder',
                'description' => 'Skilled technician responsible for welding, metal fabrication, and structural repairs on the vessel.',
                'vessel_id' => null,
                'vessel_role_access_id' => null,
            ],
            [
                'name' => 'Safety Officer',
                'description' => 'Safety specialist responsible for safety training, inspections, emergency procedures, and compliance with safety regulations.',
                'vessel_id' => null,
                'vessel_role_access_id' => null,
            ],
            [
                'name' => 'Medical Officer',
                'description' => 'Medical professional responsible for crew health, medical emergencies, first aid, and maintaining medical supplies.',
                'vessel_id' => null,
                'vessel_role_access_id' => null,
            ],
            [
                'name' => 'Crane Operator',
                'description' => 'Specialized operator responsible for operating deck cranes, cargo handling equipment, and lifting operations.',
                'vessel_id' => null,
                'vessel_role_access_id' => null,
            ],
            [
                'name' => 'Helmsman',
                'description' => 'Specialized deck crew member responsible for steering the vessel, particularly during navigation and maneuvering.',
                'vessel_id' => null,
                'vessel_role_access_id' => null,
            ],
            [
                'name' => 'Lookout',
                'description' => 'Deck crew member assigned to watch duties, monitoring surroundings for hazards, other vessels, and navigation aids.',
                'vessel_id' => null,
                'vessel_role_access_id' => null,
            ],
            [
                'name' => 'Trainee',
                'description' => 'Entry-level position for crew members in training, learning vessel operations and gaining maritime experience.',
                'vessel_id' => null,
                'vessel_role_access_id' => null,
            ],
            [
                'name' => 'Cadet',
                'description' => 'Maritime student or apprentice officer gaining practical experience and training for officer certification.',
                'vessel_id' => null,
                'vessel_role_access_id' => null,
            ],
            [
                'name' => 'Supercargo',
                'description' => 'Cargo specialist responsible for cargo operations, documentation, and ensuring proper handling and stowage of cargo.',
                'vessel_id' => null,
                'vessel_role_access_id' => null,
            ],
            [
                'name' => 'Pilot',
                'description' => 'Local navigation specialist who guides vessels through specific ports, channels, or waterways requiring specialized knowledge.',
                'vessel_id' => null,
                'vessel_role_access_id' => null,
            ],
        ];

        foreach ($positions as $position) {
            \App\Models\CrewPosition::updateOrCreate(
                [
                    'name' => $position['name'],
                    'vessel_id' => $position['vessel_id'],
                ],
                $position
            );
        }
    }
}
