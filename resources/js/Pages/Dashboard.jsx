import React, { useState, useCallback, lazy, Suspense } from 'react';
import { usePage, router } from '@inertiajs/react';
import Card from '@/Components/ui/Card';
import Selector from '@/Components/ui/Selector';
import { IoSendSharp } from "react-icons/io5";
import { FaTools, FaCheckCircle } from "react-icons/fa";
import { MdPendingActions, MdOutlineAccessTimeFilled } from "react-icons/md";
import Label from '../Components/ui/Label';

const Modal = lazy(() => import('@/Components/ui/Modal'));

const Dashboard = () => {
    const { komplainData, updateData, selectedYear, selectedMonth } = usePage().props;

    const [month, setMonth] = useState(selectedMonth);
    const [year, setYear] = useState(selectedYear);
    const [modalOpen, setModalOpen] = useState(false);
    const [modalData, setModalData] = useState({});
    const [modalTitle, setModalTitle] = useState('');
    const [currentService, setCurrentService] = useState('');

    const handleSelectionChange = useCallback(({ month, year }) => {
        setMonth(month);
        setYear(year);
        router.get('/', { month, year }, {
            preserveState: true,
            preserveScroll: true,
        });
    }, []);

    const openModal = useCallback((data, type, prefix, service) => {
        let detailData = data.detailStatus?.[type] || [];

        if (detailData.length > 0) {
            setModalData({ [type]: detailData });
            setModalTitle(`Detail Data ${type.charAt(0).toUpperCase() + type.slice(1)} - ${prefix}`);
            setCurrentService(service);
            setModalOpen(true);
        } else {
            console.log(`No detail data available for ${prefix} ${type}`);
        }
    }, []);

    const createCards = (data, prefix, service) => [
        { name: 'Menunggu', icon: <IoSendSharp />, bgColor: 'bg-sky-200', value: data.totalStatus?.['Terkirim'] || 0, hasDetail: true, detailType: 'Terkirim', tooltipText: `Jumlah ${prefix} yang terkirim, namun belum diproses oleh tim IT.` },
        { name: 'Proses', icon: <FaTools />, bgColor: 'bg-yellow-200', value: data.totalStatus?.['Dalam Pengerjaan'] || 0, hasDetail: true, detailType: 'Dalam Pengerjaan', tooltipText: `Jumlah ${prefix} yang sedang diproses oleh tim IT.` },
        { name: 'Selesai', icon: <FaCheckCircle />, bgColor: 'bg-green', value: data.totalStatus?.['Selesai'] || 0, hasDetail: true, detailType: 'Selesai', tooltipText: `Jumlah ${prefix} yang sudah berhasil diselesaikan.` },
        { name: 'Pending', icon: <MdPendingActions />, bgColor: 'bg-slate-200', value: data.totalStatus?.['Pending'] || 0, hasDetail: true, detailType: 'Pending', tooltipText: `Jumlah ${prefix} yang ditunda.` },
        { name: 'Respon Time', icon: <MdOutlineAccessTimeFilled />, bgColor: 'bg-orange-200', value: data.totalData?.respon_time || 'N/A', hasDetail: false, tooltipText: `Rata-rata waktu respon untuk menangani ${prefix}.` },
        { name: 'Durasi Pengerjaan', icon: <MdOutlineAccessTimeFilled />, bgColor: 'bg-violet-200', value: data.totalData?.durasi_pengerjaan || 'N/A', hasDetail: false, tooltipText: `Rata-rata durasi waktu pengerjaan untuk menyelesaikan ${prefix}.` },
    ].map(card => ({ ...card, service }));

    const komplainCards = createCards(komplainData, 'komplain', 'komplain');
    const updateCards = createCards(updateData, 'permintaan update', 'update');

    return (
        <div className="py-2">
            <div className="max-w-screen-2xl h-max mx-auto sm:px-6 lg:px-2">
                <div className="overflow-hidden sm:rounded-lg">
                    <div className="p-2">
                        <Selector
                            title={`Dashboard IT Bulan ${komplainData.data_bulan.find(m => m.value === month)?.label} ${year}`}
                            onSelectionChange={handleSelectionChange}
                            data_bulan={komplainData.data_bulan}
                            data_tahun={komplainData.data_tahun}
                            selectedMonth={month}
                            selectedYear={year}
                        />

                        <h2 className="text-xl font-bold mb-2 mt-8">Komplain IT</h2>
                        <Label lastUpdateTime={komplainData.lastUpdateTime} prefix="Data komplain terbaru masuk" />
                        <h3 className='text-base lg:text-lg font-bold text-white mb-4'>
                            <span className='bg-light-green py-2 px-3 rounded'>
                                {`Total Komplain: ${komplainData.totalData?.total_komplain || 0}`}
                            </span>
                        </h3>
                        <div className='grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8'>
                            {komplainCards.map((card, index) => (
                                <Card
                                    key={`komplain-${index}`}
                                    {...card}
                                    onClick={card.hasDetail ? () => openModal(komplainData, card.detailType, 'Komplain', 'komplain') : undefined}
                                />
                            ))}
                        </div>

                        <h2 className="text-xl font-bold mb-2 mt-12">Permintaan Update IT</h2>
                        <Label lastUpdateTime={updateData.lastUpdateTime} prefix="Data permintaan update terbaru masuk" />
                        <h3 className='text-base lg:text-lg font-bold text-white mb-4'>
                            <span className='bg-light-green py-2 px-3 rounded'>
                                {`Total Permintaan: ${updateData.totalData?.total_update || 0}`}
                            </span>
                        </h3>
                        <div className='grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4'>
                            {updateCards.map((card, index) => (
                                <Card
                                    key={`update-${index}`}
                                    {...card}
                                    onClick={card.hasDetail ? () => openModal(updateData, card.detailType, 'Permintaan Update', 'update') : undefined}
                                />
                            ))}
                        </div>

                        <Suspense fallback={<div>Loading...</div>}>
                            <Modal
                                isOpen={modalOpen}
                                onClose={() => setModalOpen(false)}
                                data={modalData}
                                title={modalTitle}
                                service={currentService}
                            />
                        </Suspense>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default React.memo(Dashboard);