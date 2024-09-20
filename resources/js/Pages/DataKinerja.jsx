import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { usePage, router } from '@inertiajs/react';
import BarChart from '../Components/charts/BarChart';
import Selector from '../Components/ui/Selector';
import Tables from '../Components/ui/Table';

const DataKinerja = () => {
    const { totalData, data_bulan, data_tahun, selectedMonth, selectedYear, petugasData } = usePage().props;

    const [month, setMonth] = useState(selectedMonth);
    const [year, setYear] = useState(selectedYear);

    const handleSelectionChange = useCallback(({ month, year }) => {
        setMonth(month);
        setYear(year);
        router.get('/komplain/data-kinerja', { month, year }, {
            preserveState: true,
            preserveScroll: true,
            only: ['totalData', 'selectedMonth', 'selectedYear', 'petugasData'],
        });
    }, []);

    useEffect(() => {
        if (month !== selectedMonth || year !== selectedYear) {
            handleSelectionChange({ month, year });
        }
    }, [month, year, selectedMonth, selectedYear, handleSelectionChange]);

    const processedData = useMemo(() => {
        if (!petugasData || typeof petugasData !== 'object') {
            console.error('Invalid petugasData:', petugasData);
            return { chartData: {}, tableData: [] };
        }

        let chartData = {};
        let tableData = [];
        let totalKomplain = 0;

        Object.entries(petugasData).forEach(([nama, total_komplain]) => {
            chartData[nama] = total_komplain;
            totalKomplain += total_komplain;
        });

        tableData = Object.entries(petugasData).map(([nama, total_komplain]) => ({
            nama,
            jumlahPengerjaan: total_komplain,
            kontribusi: `${((total_komplain / totalKomplain) * 100).toFixed(2)}%`
        }));

        return { chartData, tableData };
    }, [petugasData]);

    return (
        <div className="py-2">
            <div className="max-w-screen-2xl h-max mx-auto sm:px-6 lg:px-2">
                <div className="overflow-hidden sm:rounded-lg">
                    <div className="p-2">
                        <Selector
                            title={`Laporan Kinerja Petugas IT Bulan ${data_bulan.find(m => m.value === month)?.label} ${year}`}
                            onSelectionChange={handleSelectionChange}
                            data_bulan={data_bulan}
                            data_tahun={data_tahun}
                            selectedMonth={month}
                            selectedYear={year}
                        />
                        <h3 className='text-base lg:text-lg font-bold text-white mb-4'>
                            <span className='bg-light-green py-2 px-3 rounded'>
                                {`Total Komplain: ${totalData?.total_komplain || 0}`}
                            </span>
                        </h3>
                        {Object.keys(processedData.chartData).length > 0 ? (
                            <>
                                <BarChart
                                    data={processedData.chartData}
                                    title="Kinerja Petugas"
                                    label="Total Komplain Ditangani"
                                />
                                <Tables data={processedData.tableData} />
                            </>
                        ) : (
                            <p>No data available for the chart and table.</p>
                        )}
                    </div>
                </div>
            </div>
        </div>
    );
};

export default React.memo(DataKinerja);