import React from 'react';
import { Listbox, Transition } from '@headlessui/react';


const Selector = ({ title, onSelectionChange, data_bulan, data_tahun, selectedMonth, selectedYear }) => {
  const handleChange = (field, value) => {
    onSelectionChange({
      month: field === 'month' ? value : selectedMonth,
      year: field === 'year' ? value : selectedYear,
    });
  };

  return (
    <div className="flex justify-between items-center mb-2 relative">
      <h1 className="text-base lg:text-2xl font-bold text-gray-900">{title}</h1>
      <div className="flex space-x-4">
        <Listbox value={selectedMonth} onChange={(value) => handleChange('month', value)}>
          <div className="relative w-44">
            <Listbox.Button className="relative w-full cursor-default rounded-lg bg-white py-2 pl-3 pr-10 text-left shadow-md focus:outline-none focus-visible:border-indigo-500 focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-opacity-75 focus-visible:ring-offset-2 focus-visible:ring-offset-gray-300 sm:text-sm">
              <span className="block whitespace-normal">
                {data_bulan.find(m => m.value === selectedMonth)?.label || 'Select Month'}
              </span>
              <span className="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2">
                <svg
                  role="img"
                  xmlns="http://www.w3.org/2000/svg"
                  viewBox="0 0 20 20"
                  width="20px"
                  height="20px"
                >
                  <path
                    fill="#1f2937"
                    fill-rule="evenodd"
                    d="M10.53 3.47a.75.75 0 0 0-1.06 0L6.22 6.72a.75.75 0 0 0 1.06 1.06L10 5.06l2.72 2.72a.75.75 0 1 0 1.06-1.06zm-4.31 9.81l3.25 3.25a.75.75 0 0 0 1.06 0l3.25-3.25a.75.75 0 1 0-1.06-1.06L10 14.94l-2.72-2.72a.75.75 0 0 0-1.06 1.06"
                    clip-rule="evenodd"
                  />
                </svg>

              </span>
            </Listbox.Button>
            <Transition
              as={React.Fragment}
              leave="transition ease-in duration-100"
              leaveFrom="opacity-100"
              leaveTo="opacity-0"
            >
              <Listbox.Options className="absolute z-10 mt-1 max-h-60 w-full min-w-full overflow-auto rounded-md bg-white py-1 text-base shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none sm:text-sm">
                {data_bulan.map((month) => (
                  <Listbox.Option
                    key={month.value}
                    className={({ active }) =>
                      `relative cursor-default select-none py-2 pl-10 pr-4 ${active ? 'bg-amber-100 text-amber-900' : 'text-gray-900'
                      }`
                    }
                    value={month.value}
                  >
                    {({ selected }) => (
                      <span className={`block whitespace-normal ${selected ? 'font-medium' : 'font-normal'}`}>
                        {month.label}
                      </span>
                    )}
                  </Listbox.Option>
                ))}
              </Listbox.Options>
            </Transition>
          </div>
        </Listbox>

        <Listbox value={selectedYear} onChange={(value) => handleChange('year', value)}>
          <div className="relative w-32">
            <Listbox.Button className="relative w-full cursor-default rounded-lg bg-white py-2 pl-3 pr-10 text-left shadow-md focus:outline-none focus-visible:border-indigo-500 focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-opacity-75 focus-visible:ring-offset-2 focus-visible:ring-offset-gray-300 sm:text-sm">
              <span className="block whitespace-normal">
                {data_tahun.find(y => y.value === selectedYear)?.label || 'Select Year'}
              </span>
              <span className="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2">
                <svg
                  role="img"
                  xmlns="http://www.w3.org/2000/svg"
                  viewBox="0 0 20 20"
                  width="20px"
                  height="20px"
                >
                  <path
                    fill="#1f2937"
                    fill-rule="evenodd"
                    d="M10.53 3.47a.75.75 0 0 0-1.06 0L6.22 6.72a.75.75 0 0 0 1.06 1.06L10 5.06l2.72 2.72a.75.75 0 1 0 1.06-1.06zm-4.31 9.81l3.25 3.25a.75.75 0 0 0 1.06 0l3.25-3.25a.75.75 0 1 0-1.06-1.06L10 14.94l-2.72-2.72a.75.75 0 0 0-1.06 1.06"
                    clip-rule="evenodd"
                  />
                </svg>
              </span>
            </Listbox.Button>
            <Transition
              as={React.Fragment}
              leave="transition ease-in duration-100"
              leaveFrom="opacity-100"
              leaveTo="opacity-0"
            >
              <Listbox.Options className="absolute z-10 mt-1 max-h-60 w-full min-w-full overflow-auto rounded-md bg-white py-1 text-base shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none sm:text-sm">
                {data_tahun.map((year) => (
                  <Listbox.Option
                    key={year.value}
                    className={({ active }) =>
                      `relative cursor-default select-none py-2 pl-10 pr-4 ${active ? 'bg-amber-100 text-amber-900' : 'text-gray-900'
                      }`
                    }
                    value={year.value}
                  >
                    {({ selected }) => (
                      <span className={`block whitespace-normal ${selected ? 'font-medium' : 'font-normal'}`}>
                        {year.label}
                      </span>
                    )}
                  </Listbox.Option>
                ))}
              </Listbox.Options>
            </Transition>
          </div>
        </Listbox>
      </div>
    </div>
  );
};

export default React.memo(Selector);
