import React, { useState, useEffect, useMemo, lazy, useCallback } from 'react';
import Loading from '../Components/ui/Loading'
import Selector from '../Components/ui/Selector';
import { router, usePage } from '@inertiajs/react';

const XBarChart = lazy(() => import('../Components/charts/XBarChart'));
const PieChart = lazy(() => import('../Components/charts/PieChart'));

const colorPalette = ['#B414EF', '#0FBB98', '#FFCE56', '#A10E48', '#C1F39B', '#2EE4F3', '#577F8A', '#7CFC00'];

const statusColors = {
    'Terkirim': '#36A2EB',
    'Proses': '#F79D23',
    'Selesai': '#32CD32',
    'Pending': '#3E5F8A'
};

const statusOrder = ['Terkirim', 'Proses', 'Selesai', 'Pending'];

const DataUnit = ({ detailUnit, totalUnit, selectedMonth, selectedYear, data_bulan, data_tahun }) => {
    const [selectedUnit, setSelectedUnit] = useState('');
    const [month, setMonth] = useState(selectedMonth);
    const [year, setYear] = useState(selectedYear);
    const [isLoading, setIsLoading] = useState(false);
    const { props } = usePage();

    useEffect(() => {
        if (detailUnit) {
            const availableUnits = Object.keys(detailUnit);
            setSelectedUnit(availableUnits.length > 0 ? availableUnits[0] : '');
        }
    }, [detailUnit]);

    useEffect(() => {
        const handleStart = (event) => {
            if (event.detail.visit.url.includes('/komplain/data-unit')) {
                setIsLoading(true);
            }
        };
        const handleFinish = (event) => {
            if (event.detail.visit.url.includes('/komplain/data-unit')) {
                setIsLoading(false);
            }
        };

        document.addEventListener('inertia:start', handleStart);
        document.addEventListener('inertia:finish', handleFinish);

        return () => {
            document.removeEventListener('inertia:start', handleStart);
            document.removeEventListener('inertia:finish', handleFinish);
        };
    }, []);

    const handleSelectionChange = useCallback(({ month, year }) => {
        setMonth(month);
        setYear(year);
        router.get('/komplain/data-unit', { month, year }, {
            preserveState: true,
            preserveScroll: true,
            only: ['totalData', 'totalStatus', 'totalUnit', 'detailStatus', 'selectedMonth', 'selectedYear', 'detailUnit'],
        });
    }, []);

    useEffect(() => {
        if (month !== selectedMonth || year !== selectedYear) {
            handleSelectionChange({ month, year });
        }
    }, [month, year, selectedMonth, selectedYear, handleSelectionChange]);

    const serviceChartConfig = useMemo(() => {
        if (detailUnit && selectedUnit && detailUnit[selectedUnit]) {
            const unitData = detailUnit[selectedUnit];
            const labels = Object.keys(unitData);
            const data = labels.map(service => unitData[service].Total || 0);

            return {
                labels,
                datasets: [{
                    label: 'Total Komplain per Layanan',
                    data,
                    backgroundColor: colorPalette,
                }]
            };
        }
        return null;
    }, [detailUnit, selectedUnit]);

    const statusChartConfig = useMemo(() => {
        if (detailUnit && selectedUnit && detailUnit[selectedUnit]) {
            const unitData = detailUnit[selectedUnit];
            const statusData = statusOrder.reduce((acc, status) => {
                acc[status] = Object.values(unitData).reduce((sum, service) => sum + (service[status] || 0), 0);
                return acc;
            }, {});

            const data = statusOrder.map(status => statusData[status]);

            return {
                labels: statusOrder,
                datasets: [{
                    label: 'Jumlah Status',
                    data,
                    backgroundColor: statusOrder.map(status => statusColors[status]),
                }]
            };
        }
        return null;
    }, [detailUnit, selectedUnit]);

    const totalKomplainForUnit = useMemo(() => {
        return totalUnit && selectedUnit ? (totalUnit[selectedUnit]?.total || 0) : 0;
    }, [totalUnit, selectedUnit]);

    if (isLoading) {
        return <Loading />;
    }

    const availableUnits = Object.keys(detailUnit || {});

    return (
        <section className="p-2 flex-1 pt-1 mt-3 mx-2 max-w-screen-2xl h-max">
            <Selector
                title={`Laporan Komplain IT Bulan ${data_bulan.find(m => m.value === month)?.label} ${year}`}
                onSelectionChange={handleSelectionChange}
                data_bulan={data_bulan}
                data_tahun={data_tahun}
                selectedMonth={month}
                selectedYear={year}
            />
            <h3 className='mt-5 lg:mt-2 text-base lg:text-lg font-bold text-white'>
                <span className='bg-light-green py-2 px-3 rounded'>{`Total Komplain: ${totalKomplainForUnit}`}</span>
            </h3>

            {availableUnits.length > 0 ? (
                <div className="mt-6">
                    <div className="mb-8">
                        <label htmlFor="unit-select" className="mr-2">Pilih Unit:</label>
                        <select
                            id="unit-select"
                            value={selectedUnit}
                            onChange={(e) => setSelectedUnit(e.target.value)}
                            className="bg-white border border-slate-500 rounded-md p-1 text-sm"
                        >
                            {availableUnits.map((unit) => (
                                <option key={unit} value={unit}>{unit}</option>
                            ))}
                        </select>
                    </div>

                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        {serviceChartConfig ? (
                            <div>
                                <XBarChart key={`service-${month}-${year}-${selectedUnit}`} data={serviceChartConfig} />
                            </div>
                        ) : (
                            <div className="bg-white p-4 rounded-lg shadow-lg">
                                <p>No service data available for the selected unit.</p>
                            </div>
                        )}
                        {statusChartConfig ? (
                            <div>
                                <PieChart key={`status-${month}-${year}-${selectedUnit}`} data={statusChartConfig} />
                            </div>
                        ) : (
                            <div className="bg-white p-4 rounded-lg shadow-lg">
                                <p>No status data available for the selected unit.</p>
                            </div>
                        )}
                    </div>
                </div>
            ) : (
                <div className="mt-6 bg-white p-4 rounded-lg shadow-lg">
                    <p>No data available for the selected month and year.</p>
                </div>
            )}
        </section>
    );
};

export default React.memo(DataUnit);