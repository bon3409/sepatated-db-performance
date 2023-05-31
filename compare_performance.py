import pandas as pd
import matplotlib.pyplot as plt

file_base_url = './result/120_times_in_60_seconds/'
use_columns = ['timeStamp', 'elapsed']

multiple_read = pd.read_csv(file_base_url + 'multiple_read_db_get_orders.csv', usecols=use_columns)
multiple_write = pd.read_csv(file_base_url + 'multiple_write_db_create_order.csv', usecols=use_columns)
single_read = pd.read_csv(file_base_url + 'single_read_db_get_orders.csv', usecols=use_columns)
single_write = pd.read_csv(file_base_url + 'single_write_db_create_order.csv', usecols=use_columns)

df1 = pd.DataFrame(multiple_read, columns=use_columns)
df2 = pd.DataFrame(multiple_write, columns=use_columns)
df3 = pd.DataFrame(single_read, columns=use_columns)
df4 = pd.DataFrame(single_write, columns=use_columns)

plt.plot(df1.index, df1['elapsed'], label='multiple_read')
plt.plot(df2.index, df2['elapsed'], label='multiple_write')
plt.plot(df3.index, df3['elapsed'], label='single_read')
plt.plot(df4.index, df4['elapsed'], label='single_write')

plt.legend(loc='upper left')
plt.title('Read / Write separated database performance compare')
plt.xlabel('Cumulative times')
plt.ylabel('Response time (ms)')

plt.show()