<?php
// Auxiliary functions
function calculateRequiredTables($groupSize) {
    if ($groupSize <= 4) {
        return 1;
    } else {
        return ceil(($groupSize - 2) / 2);
    }
}

function totalCapacity($numTables) {
    return 4 + ($numTables - 1) * 2;
}

// Variables for statistics (optional)
$totalGroupsServed = 0;
$totalGroupsRejected = 0;

// Loop to execute the process 1,000,000 times
for ($simulation = 1; $simulation <= 1000000; $simulation++) {
    // Initialize 100 tables, each with a capacity for 4 diners
    $totalTables = 100;
    $occupiedTables = array_fill(0, $totalTables, false);
    $availableTables = range(0, $totalTables - 1); // Indices of available tables

    // Main simulation loop
    while (count($availableTables) > 0) {
        // Generate a random group size between 1 and 15
        $groupSize = rand(1, 15);

        // Calculate the minimum number of tables required
        $requiredTables = calculateRequiredTables($groupSize);

        // Check if there are enough available tables
        $availableTablesCount = count($availableTables);
        if ($availableTablesCount < $requiredTables) {
            // Not enough tables available for this group
            $totalGroupsRejected++;
            continue;
        }

        // Check if the total capacity is sufficient
        $capacity = totalCapacity($requiredTables);
        if ($capacity < $groupSize) {
            // Not enough capacity for this group
            $totalGroupsRejected++;
            continue;
        }

        // Assign tables to the group
        $assignedTables = array_splice($availableTables, 0, $requiredTables);

        // Mark the tables as occupied
        foreach ($assignedTables as $tableIndex) {
            $occupiedTables[$tableIndex] = true;
        }

        // Group served
        $totalGroupsServed++;
    }

    // Optional: Print progress or results of the simulation
    // if ($simulation % 100000 == 0) {
    //     echo "Simulation $simulation completed.\n";
    // }
}

// End of the process
echo "Completed 1,000,000 simulations.\n";
echo "Total groups served: $totalGroupsServed\n";
echo "Total groups rejected: $totalGroupsRejected\n";
?>
