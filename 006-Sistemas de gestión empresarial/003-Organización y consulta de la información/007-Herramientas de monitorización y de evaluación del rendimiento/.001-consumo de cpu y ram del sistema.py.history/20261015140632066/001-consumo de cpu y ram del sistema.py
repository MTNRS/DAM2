import psutil

# Uso de CPU (%)
cpu = psutil.cpu_percent(interval=1)

# Uso de RAM (%)
ram = psutil.virtual_memory().percent

print("CPU:", cpu, "%")
print("RAM:", ram, "%")