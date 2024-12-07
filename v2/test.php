<?php

// ==============================================
// Configuration and Data Structures (Constants)
// ==============================================

const NUM_TABLES = 100;
const MAX_CAPACITY_PER_TABLE = 4; // Each table alone can seat 4 guests
const NUM_CUSTOMERS = 200;
const START_TIME = 12 * 60; // 12:00 in minutes from start of the day
const END_TIME = 22 * 60;   // 22:00 in minutes
const INTERVAL = 15;        // 15-minute intervals
const TOTAL_ITERATIONS = 100000; // Repeat the entire simulation 100,000 times

// According to the examples:
// 1 table = 4 seats
// 2 tables combined = 6 seats
// 3 tables combined = 8 seats
// Use the derived formula: combinedCapacity(n) = n*MAX_CAPACITY_PER_TABLE - (n-1)*2
function combinedCapacity($numTables) {
    return $numTables * MAX_CAPACITY_PER_TABLE - ($numTables - 1) * 2;
}

// Time slots generation
$timeSlots = [];
for ($t = START_TIME; $t <= END_TIME; $t += INTERVAL) {
    $timeSlots[] = $t;
}

// ==============================================
// Class for Reservation DTO
// ==============================================
class ReservationDTO {
    public $id;
    public $name;
    public $phone;
    public $guests;
    public $startTime;
    public $endTime;
    public $tables; // array of table indices

    public function __construct($id, $name, $phone, $guests, $startTime, $endTime, $tables) {
        $this->id = $id;
        $this->name = $name;
        $this->phone = $phone;
        $this->guests = $guests;
        $this->startTime = $startTime;
        $this->endTime = $endTime;
        $this->tables = $tables;
    }
}

// ==============================================
// Random Data Generation Functions
// ==============================================

// Generate a random 5-letter name (a-z)
function generateRandomName() {
    $name = '';
    for ($i = 0; $i < 5; $i++) {
        $charCode = 97 + mt_rand(0, 25);
        $name .= chr($charCode);
    }
    return $name;
}

// Generate a random phone with 9 digits
function generateRandomPhone() {
    $phone = '';
    for ($i = 0; $i < 9; $i++) {
        $phone .= (string)mt_rand(0,9);
    }
    return $phone;
}

// Generate a 64-char hex ID
function generateId64Hex() {
    // Use random_bytes(32) and bin2hex to get 64 hex chars
    return bin2hex(random_bytes(32));
}

// Pre-generate customers (common for all iterations)
function generateCustomers($num) {
    $customers = [];
    for ($i = 0; $i < $num; $i++) {
        $customers[] = [
            'name' => generateRandomName(),
            'phone' => generateRandomPhone()
        ];
    }
    return $customers;
}

$customers = generateCustomers(NUM_CUSTOMERS);

// ==============================================
// Helper Functions
// ==============================================

// Check if all tables have been used at least once
function checkAllTablesUsedOnce($tables) {
    foreach ($tables as $t) {
        if ($t['timesUsed'] == 0) {
            return false;
        }
    }
    return true;
}

// Find tables for given guests
function findTablesForGuests($guestCount, $tables) {
    for ($n=1; $n<=NUM_TABLES; $n++) {
        $cap = combinedCapacity($n);
        if ($cap >= $guestCount) {
            $freeTablesIndices = [];
            for ($i=0; $i<count($tables) && count($freeTablesIndices)<$n; $i++) {
                if (!$tables[$i]['occupied']) {
                    $freeTablesIndices[] = $i;
                }
            }
            if (count($freeTablesIndices) === $n) {
                return $freeTablesIndices;
            }
        }
    }
    return null;
}

// Occupy tables
function occupyTables($tablesIndices, $guests, $startTime, &$tables, &$reservations, $customers) {
    $occupationTime = 60 + $guests * 5; // 1h + 5m per guest
    $endTime = $startTime + $occupationTime;

    foreach ($tablesIndices as $idx) {
        $tables[$idx]['occupied'] = true;
        $tables[$idx]['timesUsed'] += 1;
    }

    $randomCustomer = $customers[mt_rand(0, count($customers)-1)];

    $reservation = new ReservationDTO(
        generateId64Hex(),
        $randomCustomer['name'],
        $randomCustomer['phone'],
        $guests,
        $startTime,
        $endTime,
        $tablesIndices
    );

    $reservations[] = $reservation;
    return $reservation;
}

// Cancel reservation
function cancelReservation($resId, &$reservations, &$tables) {
    for ($i=0; $i<count($reservations); $i++) {
        if ($reservations[$i]->id === $resId) {
            $res = $reservations[$i];
            foreach ($res->tables as $ti) {
                $tables[$ti]['occupied'] = false;
            }
            array_splice($reservations, $i, 1);
            return true;
        }
    }
    return false;
}

// Modify reservation
function modifyReservation($resId, $newGuests, $newStartTime, &$reservations, &$tables, $customers, $timeSlots) {
    if (!cancelReservation($resId, $reservations, $tables)) {
        return null;
    }
    $t = findTablesForGuests($newGuests, $tables);
    if (!$t) return null;
    return occupyTables($t, $newGuests, $newStartTime, $tables, $reservations, $customers);
}

// ==============================================
// Simulation Function
// ==============================================

function runSimulation($customers, $timeSlots) {
    $tables = [];
    for ($i=0; $i<NUM_TABLES; $i++) {
        $tables[] = [
            'id' => $i,
            'occupied' => false,
            'timesUsed' => 0
        ];
    }

    $reservations = [];
    $callCount = 0;
    $allTablesUsedOnce = false;

    while (!$allTablesUsedOnce) {
        $callCount++;
        $guests = 1 + mt_rand(0,14); // random between 1 and 15
        $startTime = $timeSlots[mt_rand(0, count($timeSlots)-1)];

        // Every 20 calls, cancel a random reservation if exists
        if ($callCount % 20 === 0 && count($reservations) > 0) {
            $rndIndex = mt_rand(0, count($reservations)-1);
            $rndRes = $reservations[$rndIndex]->id;
            cancelReservation($rndRes, $reservations, $tables);
        }
        // Every 10 calls (and not 20), modify a reservation
        else if ($callCount % 10 === 0 && count($reservations) > 0) {
            $rndIndex = mt_rand(0, count($reservations)-1);
            $rndRes = $reservations[$rndIndex]->id;
            $newGuests = 1 + mt_rand(0,14);
            $newStartTime = $timeSlots[mt_rand(0, count($timeSlots)-1)];
            modifyReservation($rndRes, $newGuests, $newStartTime, $reservations, $tables, $customers, $timeSlots);
        } else {
            // Normal reservation attempt
            $t = findTablesForGuests($guests, $tables);
            if ($t) {
                occupyTables($t, $guests, $startTime, $tables, $reservations, $customers);
            }
            // If no table fits, do nothing
        }

        $allTablesUsedOnce = checkAllTablesUsedOnce($tables);

        // Safeguard
        if ($callCount > 100000) {
            break;
        }
    }

    return [
        'totalReservations' => count($reservations),
        'totalCalls' => $callCount,
        'allUsed' => $allTablesUsedOnce
    ];
}

// ==============================================
// Run the simulation 100,000 times
// ==============================================

for ($i = 0; $i < TOTAL_ITERATIONS; $i++) {
    $result = runSimulation($customers, $timeSlots);
    // For performance tests, you may want to omit any output.
    // echo "Iteration ".($i+1)."/".TOTAL_ITERATIONS.": ".json_encode($result)."\n";
}

echo "All iterations completed.\n";
