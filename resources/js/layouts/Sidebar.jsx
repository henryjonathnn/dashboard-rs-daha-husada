import React, { useState, useEffect } from 'react';
import { Link } from '@inertiajs/react';
import Logo from '../assets/logo.png';
import { FaChartPie, FaScrewdriver, FaFileUpload } from 'react-icons/fa';
import { BsPersonWorkspace } from 'react-icons/bs';
import { MdDoubleArrow, MdKeyboardArrowDown, MdKeyboardArrowUp } from 'react-icons/md';
import { GiFirstAidKit } from 'react-icons/gi';

export default function Sidebar({ collapsed, setCollapsed }) {
  const [openMenu, setOpenMenu] = useState('');
  const [activeLink, setActiveLink] = useState('');

  useEffect(() => {
    // Set the active link on initial load from localStorage
    const savedActiveLink = localStorage.getItem('activeLink');
    if (savedActiveLink) {
      setActiveLink(savedActiveLink);
    }

    // Retrieve the open submenu from localStorage on load
    const savedMenu = localStorage.getItem('openMenu');
    if (savedMenu) {
      setOpenMenu(savedMenu);
    }

    // Check if the active link is in a submenu, and if so, open that submenu
    const parentMenu = Object.keys(subMenuItems).find(menu => 
      subMenuItems[menu].some(item => item.link === savedActiveLink)
    );

    if (parentMenu) {
      setOpenMenu(parentMenu);
    }
  }, []);

  const handleMenuToggle = (menuItemLink) => {
    const newMenuState = openMenu === menuItemLink ? '' : menuItemLink;
    setOpenMenu(newMenuState);

    // Save the open submenu state to localStorage
    localStorage.setItem('openMenu', newMenuState);
  };

  const handleLinkClick = (link) => {
    setActiveLink(link);
    localStorage.setItem('activeLink', link); // Save the active link to localStorage
  };

  const mainMenuItems = [
    { name: 'Dashboard', icon: <FaChartPie />, link: '/' },
    { name: 'Data Komplain IT', icon: <FaScrewdriver />, link: '/komplain' },
    {
      name: ['Permintaan', 'Update Data IT'],
      icon: <FaFileUpload />,
      isTwoLines: true,
      link: '/permintaan-update'
    }
  ];

  const subMenuItems = {
    '/komplain': [
      { name: 'Data Unit', icon: <GiFirstAidKit />, link: '/komplain/data-unit' },
      { name: 'Data Kinerja', icon: <BsPersonWorkspace />, link: '/komplain/data-kinerja' },
    ],
    '/permintaan-update': [
      { name: 'Data Kinerja', icon: <BsPersonWorkspace />, link: '/permintaan-update/data-kinerja' },
    ]
  };

  const renderMenuItemContent = (menuItem) => (
    <>
      <div className={`flex-shrink-0 ${collapsed ? 'mr-0' : 'mr-3'} transition-all duration-300 ease-in-out`}>
        {menuItem.icon}
      </div>
      {!collapsed && (
        menuItem.isTwoLines ? (
          <div className="flex flex-col leading-tight">
            <span className="text-sm">{menuItem.name[0]}</span>
            <span className="text-sm">{menuItem.name[1]}</span>
          </div>
        ) : (
          <span className="text-sm whitespace-nowrap overflow-hidden text-ellipsis">
            {menuItem.name}
          </span>
        )
      )}
    </>
  );

  return (
    <div className={`fixed top-0 bottom-0 left-0 bg-white border-r border-gray-200 transition-all duration-300 ease-in-out ${collapsed ? 'w-16' : 'w-64'} sidebar`}>
      <div className='flex justify-end items-center mt-4'>
        <button
          className={`p-2 text-white text-lg font-bold bg-light-green rounded-md mr-1 transition-all duration-300 ease-in-out ${collapsed ? 'mr-4' : 'opacity-100 scale-100'}`}
          onClick={() => {
            setCollapsed(!collapsed);
            document.body.classList.toggle('sidebar-collapsed');
          }}
        >
          <MdDoubleArrow className={`transform ${collapsed ? 'rotate-0' : 'rotate-180'} transition-transform duration-300 ease-in-out`} />
        </button>
      </div>
      <div className='flex justify-center items-center py-4 h-40 w-full mt-24'>
        <div className={`relative p-8 border-2 border-gray-200 transition-all duration-300 ease-in-out ${collapsed ? 'opacity-0 scale-0' : 'opacity-100 scale-100'}`}>
          <img src={Logo} className={`w-40 transition-all duration-300 ease-in-out ${collapsed ? 'opacity-0 scale-0' : 'opacity-100 scale-100'}`} alt="Logo" />
        </div>
      </div>
      <nav className='mt-20'>
        {mainMenuItems.map((menuItem, index) => (
          <div key={index} className='mb-1'>
            <div className={`flex items-center w-full font-medium transition-all duration-300 ease-in-out
              ${activeLink === menuItem.link ? 'bg-light-green text-white' : 'text-gray-600 hover:bg-gray-100'}
              ${collapsed ? 'justify-center' : ''}`}>
              <Link href={menuItem.link} className={`flex items-center w-full px-4 py-3 ${menuItem.isTwoLines ? 'h-16' : 'h-12'}`} onClick={() => handleLinkClick(menuItem.link)}>
                {renderMenuItemContent(menuItem)}
              </Link>
              {subMenuItems[menuItem.link] && (
                <button
                  onClick={(e) => {
                    e.preventDefault();
                    handleMenuToggle(menuItem.link);
                  }}
                  className={`p-3 text-current hover:bg-transparent transition-colors duration-300 ease-in-out ${collapsed ? 'hidden' : ''}`}
                >
                  {openMenu === menuItem.link ? <MdKeyboardArrowUp /> : <MdKeyboardArrowDown />}
                </button>
              )}
            </div>
            <div className={`overflow-hidden transition-all duration-300 ease-in-out ${openMenu === menuItem.link ? 'max-h-40' : 'max-h-0'}`}>
              {subMenuItems[menuItem.link]?.map((item, index) => (
                <Link
                  href={item.link}
                  key={index}
                  className={`flex items-center px-8 py-2 text-gray-600 hover:bg-gray-100 transition-colors duration-300 ease-in-out ${activeLink === item.link ? 'bg-light-green text-white' : ''}`}
                  onClick={() => handleLinkClick(item.link)}
                >
                  <div className={`flex-shrink-0 ${collapsed ? 'mr-0' : 'mr-3'}`}>
                    {item.icon}
                  </div>
                  {!collapsed && <span className="ml-2 text-sm">{item.name}</span>}
                </Link>
              ))}
            </div>
          </div>
        ))}
      </nav>
    </div>
  );
}
