import random

# Auxiliary functions
def calculate_required_tables(group_size):
    if group_size <= 4:
        return 1
    else:
        return -(-(group_size - 2) // 2)  # Equivalent to math.ceil((group_size - 2) / 2)

def total_capacity(num_tables):
    return 4 + (num_tables - 1) * 2

# Variables for statistics (optional)
total_groups_served = 0
total_groups_rejected = 0

# Loop to execute the process 1,000,000 times
for simulation in range(1, 1000001):
    # Initialize 100 tables with 0 diners (indicating they are empty)
    total_tables = 100
    occupied_tables = [0] * total_tables  # Store the number of diners per table
    available_table_indices = list(range(total_tables))  # Indices of available tables

    # Main simulation loop
    while len(available_table_indices) > 0:
        # Generate a random group size between 1 and 15
        group_size = random.randint(1, 15)

        # Calculate the minimum number of tables required
        required_tables = calculate_required_tables(group_size)

        # Check if there are enough available tables
        available_tables_count = len(available_table_indices)
        if available_tables_count < required_tables:
            # Not enough tables available for this group
            total_groups_rejected += 1
            continue

        # Check if the total capacity is sufficient
        capacity = total_capacity(required_tables)
        if capacity < group_size:
            # Not enough capacity for this group
            total_groups_rejected += 1
            continue

        # Assign tables to the group
        assigned_tables = available_table_indices[:required_tables]

        # Remove assigned tables from available_table_indices
        available_table_indices = available_table_indices[required_tables:]

        # Distribute diners across assigned tables
        remaining_diners = group_size
        for table_index in assigned_tables:
            if remaining_diners > 4:
                occupied_tables[table_index] = 4  # Full table
                remaining_diners -= 4
            else:
                occupied_tables[table_index] = remaining_diners  # Partial table
                remaining_diners = 0

        # Group served
        total_groups_served += 1

    # Optional: Print progress or results of the simulation
    # if simulation % 100000 == 0:
    #     print(f"Simulation {simulation} completed.")

# End of the process
print("Completed 1,000,000 simulations.")
print(f"Total groups served: {total_groups_served}")
print(f"Total groups rejected: {total_groups_rejected}")
