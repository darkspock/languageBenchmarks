package main

import (
    "fmt"
    "math/rand"
    "time"
)

// Auxiliary functions
func calculateRequiredTables(groupSize int) int {
    if groupSize <= 4 {
        return 1
    } else {
        return (groupSize - 2 + 1) / 2 // Equivalent to Math.ceil((groupSize - 2) / 2)
    }
}

func totalCapacity(numTables int) int {
    return 4 + (numTables-1)*2
}

func main() {
    // Seed the random number generator
    rand.Seed(time.Now().UnixNano())

    // Variables for statistics (optional)
    totalGroupsServed := 0
    totalGroupsRejected := 0

    // Loop to execute the process 1,000,000 times
    simulations := 1000000
    for simulation := 1; simulation <= simulations; simulation++ {
        // Initialize 100 tables, each with a capacity for 4 diners
        totalTables := 100
        occupiedTables := make([]bool, totalTables)
        availableTableIndices := make([]int, totalTables)
        for i := 0; i < totalTables; i++ {
            availableTableIndices[i] = i
        }

        // Main simulation loop
        for len(availableTableIndices) > 0 {
            // Generate a random group size between 1 and 15
            groupSize := rand.Intn(15) + 1

            // Calculate the minimum number of tables required
            requiredTables := calculateRequiredTables(groupSize)

            // Check if there are enough available tables
            availableTablesCount := len(availableTableIndices)
            if availableTablesCount < requiredTables {
                // Not enough tables available for this group
                totalGroupsRejected++
                continue
            }

            // Check if the total capacity is sufficient
            capacity := totalCapacity(requiredTables)
            if capacity < groupSize {
                // Not enough capacity for this group
                totalGroupsRejected++
                continue
            }

            // Assign tables to the group
            assignedTables := availableTableIndices[:requiredTables]

            // Remove assigned tables from availableTableIndices
            availableTableIndices = availableTableIndices[requiredTables:]

            // Mark the tables as occupied
            for _, tableIndex := range assignedTables {
                occupiedTables[tableIndex] = true
            }

            // Group served
            totalGroupsServed++
        }

        // Optional: Print progress or results of the simulation
        /*
        if simulation%100000 == 0 {
            fmt.Printf("Simulation %d completed.\n", simulation)
        }
        */
    }

    // End of the process
    fmt.Println("Completed 1,000,000 simulations.")
    fmt.Printf("Total groups served: %d\n", totalGroupsServed)
    fmt.Printf("Total groups rejected: %d\n", totalGroupsRejected)
}
