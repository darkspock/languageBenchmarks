// Auxiliary functions
function calculateRequiredTables(groupSize) {
    if (groupSize <= 4) {
        return 1;
    } else {
        return Math.ceil((groupSize - 2) / 2);
    }
}

function totalCapacity(numTables) {
    return 4 + (numTables - 1) * 2;
}

// Variables for statistics (optional)
let totalGroupsServed = 0;
let totalGroupsRejected = 0;

// Loop to execute the process 1,000,000 times
for (let simulation = 1; simulation <= 1000000; simulation++) {
    // Initialize 100 tables with 0 diners (indicating they are empty)
    const totalTables = 100;
    let occupiedTables = new Array(totalTables).fill(0); // Stores the number of diners per table
    let availableTableIndices = [...Array(totalTables).keys()]; // Indices of available tables

    // Main simulation loop
    while (availableTableIndices.length > 0) {
        // Generate a random group size between 1 and 15
        let groupSize = Math.floor(Math.random() * 15) + 1;

        // Calculate the minimum number of tables required
        let requiredTables = calculateRequiredTables(groupSize);

        // Check if there are enough available tables
        let availableTablesCount = availableTableIndices.length;
        if (availableTablesCount < requiredTables) {
            // Not enough tables available for this group
            totalGroupsRejected++;
            continue;
        }

        // Check if the total capacity is sufficient
        let capacity = totalCapacity(requiredTables);
        if (capacity < groupSize) {
            // Not enough capacity for this group
            totalGroupsRejected++;
            continue;
        }

        // Assign tables to the group
        let assignedTables = availableTableIndices.splice(0, requiredTables);

        // Distribute diners across assigned tables
        let remainingDiners = groupSize;
        for (let tableIndex of assignedTables) {
            if (remainingDiners > 4) {
                occupiedTables[tableIndex] = 4; // Full table
                remainingDiners -= 4;
            } else {
                occupiedTables[tableIndex] = remainingDiners; // Partial table
                remainingDiners = 0;
            }
        }

        // Group served
        totalGroupsServed++;
    }

    // Optional: Print progress or results of the simulation
    // if (simulation % 100000 === 0) {
    //     console.log(`Simulation ${simulation} completed.`);
    // }
}

// End of the process
console.log("Completed 1,000,000 simulations.");
console.log(`Total groups served: ${totalGroupsServed}`);
console.log(`Total groups rejected: ${totalGroupsRejected}`);
